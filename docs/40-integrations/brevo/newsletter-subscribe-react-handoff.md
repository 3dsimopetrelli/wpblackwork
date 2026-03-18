# Blackwork Newsletter Subscribe — React Implementation Handoff

Technical reference for reimplementing the Blackwork newsletter subscription system in a React app.
Written for a developer who needs to understand the existing flow, reproduce its behavior, and integrate with the same Brevo account.

---

## 1. What the Widget Is

The Blackwork newsletter subscribe form is a custom Elementor widget (`bw-newsletter-subscription`) embedded in the WordPress site. Its sole purpose is to collect newsletter signups and push them into the Brevo double opt-in (DOI) flow.

Key design decisions:
- **Fixed design.** No per-instance style controls. Appearance is controlled by CSS, not operator settings.
- **Configuration-driven behavior.** All Brevo list IDs, opt-in mode, sender identity, and copy live in an admin settings panel — not in code.
- **Server-side Brevo calls only.** The Brevo API key is never exposed to the browser.
- **Consent-gated.** No subscription without explicit privacy checkbox.
- **GDPR-safe.** Detailed Brevo errors are logged server-side only; the user receives only safe generic messages.

The React app needs to reproduce this behavior with its own backend endpoint and configuration layer.

---

## 2. Current Blackwork Flow

This is the full intended sequence for a double opt-in subscription:

```
1. User fills in the form (name optional, email required, privacy checkbox)
2. Frontend validates client-side (empty email, invalid email, missing consent)
3. Frontend submits to backend endpoint
4. Backend validates again server-side
5. Backend runs cooldown gate (45-second per email+IP window)
6. Backend checks if contact is already in the Marketing list → returns already_subscribed if yes
7. Backend calls Brevo: upsert_contact(email, attributes, listIds=[unconfirmed_list_id])
   → contact is created/updated in Brevo immediately
   → contact is assigned to Blackwork – Unconfirmed (#11) immediately
   → contact is now visible in the Brevo panel before confirmation
8. Backend calls Brevo: send_double_opt_in(email, templateId, redirectUrl, listIds=[list_id], attributes, sender)
   → DOI confirmation email is sent to the subscriber
   → Brevo will assign the contact to Blackwork – Marketing (#10) only after the subscriber clicks the confirmation link
9. Backend returns success response to frontend
10. Frontend shows success message, resets form
11. Subscriber receives DOI email, clicks confirmation link
12. Brevo assigns contact to Blackwork – Marketing (#10)
13. Welcome email automation fires in Brevo (triggered by: added to Marketing list)
```

Critical constraint: steps 7 and 8 are always sequential. Step 8 (DOI email) fires regardless of whether step 7 (upsert) succeeds — the upsert is non-fatal. If the upsert fails, the confirmation email is still sent.

---

## 3. Brevo Lists

### Blackwork – Unconfirmed (`#11`)

- **Purpose:** Holds contacts who have submitted the form but have not yet clicked the DOI confirmation link.
- **When assigned:** Immediately on form submit, via an explicit `upsert_contact` call before `send_double_opt_in`.
- **Why this matters:** Brevo's DOI API (`/contacts/doubleOptinConfirmation`) only assigns the contact to the Marketing list *after* confirmation. Without the explicit pre-assign step, the contact does not appear in any Brevo list until they confirm — making pending subscribers invisible in the panel.
- **Removal:** The plugin does not remove contacts from this list after confirmation. This should be handled by a Brevo Automation: trigger = added to Marketing list (#10) → remove from Unconfirmed list (#11).

### Blackwork – Marketing (`#10`)

- **Purpose:** Holds confirmed subscribers who have explicitly opted in and clicked the confirmation link.
- **When assigned:** By Brevo automatically after the subscriber clicks the DOI confirmation link.
- **Trigger for automations:** Welcome email and other marketing automations fire on `added to this list`.

---

## 4. Mail Marketing Settings Panel

The WordPress plugin stores all Brevo configuration in the option `bw_mail_marketing_general_settings`. The React app needs equivalent configuration — either as environment variables, a backend config file, or an admin interface.

| Setting | WordPress key | Description |
|---|---|---|
| Brevo API key | `api_key` | Server-side only. Never exposed to browser. Used to authenticate all Brevo API calls. |
| API base URL | (hardcoded in client) | `https://api.brevo.com/v3`. |
| Main list | `list_id` | Numeric Brevo list ID for the Marketing list (#10). Assigned to contacts after DOI confirmation. |
| Unconfirmed list | `unconfirmed_list_id` | Numeric Brevo list ID for the Unconfirmed list (#11). Assigned immediately on submit before DOI email. Set to `0` to skip pre-assign. |
| Default opt-in mode | `default_optin_mode` | `single_opt_in` or `double_opt_in`. The React app is expected to use `double_opt_in`. |
| DOI template ID | `double_optin_template_id` | Numeric Brevo template ID for the DOI confirmation email. Must be a Brevo-managed template — see Section 7. |
| DOI redirect URL | `double_optin_redirect_url` | URL the subscriber lands on after clicking the confirmation link. Typically a "You're confirmed" page on the site. |
| Sender name | `sender_name` | Display name in the From field of the DOI email. |
| Sender email | `sender_email` | From address for the DOI email. **Must be the authenticated domain email** (`hello@blackwork.pro`), not Gmail or any unauthenticated address — see Section 6. |

The React backend should read all of these from environment variables or a secure config source. None of them should be hardcoded in application code.

---

## 5. Technical Integration Requirements

### Frontend form (React component)

Required fields:
- `name` — optional text input, used for `FIRSTNAME` / `LASTNAME` Brevo attributes when provided
- `email` — required text input
- `privacy` — required checkbox (consent gate)

Required states:
- idle
- loading (submit in progress, button disabled)
- success (form reset, success message shown)
- error (inline message shown, relevant field marked invalid)

Required client-side validation (mirrors server-side):
- empty email → show `emptyEmail` message, mark email field invalid
- invalid email format → show `invalidEmail` message, mark email field invalid
- privacy not checked (when consent required) → show `missingConsent` message, mark privacy field invalid
- normalize email before send: `trim()` + collapse whitespace + `toLowerCase()`

Double-submit prevention:
- track a `busy` flag; ignore additional submits while a request is in flight

### Backend endpoint

The backend must:
1. Accept `POST` with: `email`, `name` (optional), `consent` (boolean)
2. Validate input server-side (same rules as frontend — do not trust client)
3. Normalize email: trim + lowercase
4. Run cooldown check: 45-second window keyed by normalized email + request IP hash
5. Look up contact in Brevo: if already in Marketing list (`list_id`), return `already_subscribed`
6. Call Brevo `upsert_contact` with Unconfirmed list (`unconfirmed_list_id`) if configured
7. Call Brevo `send_double_opt_in` with Marketing list (`list_id`), template, redirect URL, sender
8. Return structured JSON response (see response contract below)
9. Log Brevo/provider errors server-side only — never forward raw errors to the client

### Brevo API integration

Two Brevo API calls are required per successful DOI submit:

**Call 1 — upsert contact and assign to Unconfirmed list:**
```
POST https://api.brevo.com/v3/contacts
Authorization: api-key {BREVO_API_KEY}
Content-Type: application/json

{
  "email": "user@example.com",
  "attributes": {
    "FIRSTNAME": "Mario",
    "LASTNAME": "Rossi",
    "SOURCE": "react_app",
    "CONSENT_SOURCE": "react_app",
    "CONSENT_AT": "2026-03-18T10:15:00Z",
    "CONSENT_STATUS": "granted",
    "BW_ORIGIN_SYSTEM": "app",
    "BW_ENV": "production"
  },
  "listIds": [11],
  "updateEnabled": true
}
```

**Call 2 — send DOI confirmation email:**
```
POST https://api.brevo.com/v3/contacts/doubleOptinConfirmation
Authorization: api-key {BREVO_API_KEY}
Content-Type: application/json

{
  "email": "user@example.com",
  "templateId": 7,
  "redirectionUrl": "https://blackwork.pro/newsletter-confirmed/",
  "includeListIds": [10],
  "attributes": {
    "FIRSTNAME": "Mario",
    "LASTNAME": "Rossi"
  },
  "sender": {
    "name": "Blackwork",
    "email": "hello@blackwork.pro"
  }
}
```

After the subscriber clicks the confirmation link in the email, Brevo automatically adds them to list `#10` (Marketing). The React backend does not need to handle this step — it is entirely Brevo-side.

### Response contract

The backend endpoint must return this structure:

```json
{
  "success": true,
  "data": {
    "code": "success",
    "message": "Thank you! Check your inbox to confirm your subscription."
  }
}
```

For errors:
```json
{
  "success": false,
  "data": {
    "code": "rate_limited",
    "message": "Please wait a moment before trying again."
  }
}
```

**Stable response codes** (the frontend switches on `data.code`):

| Code | Meaning | HTTP success? |
|---|---|---|
| `success` | DOI email sent | `true` |
| `already_subscribed` | Contact already in Marketing list | `true` |
| `empty_email` | Email field was empty | `false` |
| `invalid_email` | Email failed format validation | `false` |
| `missing_consent` | Privacy checkbox not checked | `false` |
| `rate_limited` | Cooldown window active | `false` |
| `generic_failure` | Brevo error or unexpected failure | `false` |

### Accessibility requirements

- Explicit `<label>` for every input
- `aria-describedby` linking inputs to hint/error text
- `aria-invalid="true"` on fields with validation errors
- `aria-disabled="true"` on submit button during loading
- `aria-live="polite"` on the message region (use `assertive` for errors)
- `aria-busy="true"` on the button during loading

---

## 6. Important Implementation Notes

**Unconfirmed contacts must be explicitly pre-assigned before DOI.**
Brevo's `/contacts/doubleOptinConfirmation` endpoint does not add the contact to any list until after confirmation. Skipping the `upsert_contact` step means contacts are invisible in all Brevo lists until they confirm. Always call `upsert_contact` with `listIds: [unconfirmed_list_id]` before calling `doubleOptinConfirmation`.

**After confirmation, Brevo handles the list move automatically.**
The `includeListIds` in the DOI call is what Brevo assigns the contact to after confirmation. The React backend does not need to do anything else post-confirmation.

**No hardcoded list IDs.**
Both `list_id` (Marketing) and `unconfirmed_list_id` (Unconfirmed) must come from configuration. The Blackwork system has a fallback to ID `10` for backward compatibility, but new implementations must not rely on hardcoded values — list IDs can change.

**Sender must use the authenticated domain email.**
The `sender.email` in the DOI call must match a domain that is SPF/DKIM/DMARC authenticated in Brevo. Using a Gmail address or any unauthenticated address will cause Brevo to reject the call or result in delivery failures. For Blackwork, the only valid sender is `hello@blackwork.pro`.

**The upsert step is non-fatal.**
If the `upsert_contact` call fails (e.g., due to missing custom attributes), the DOI email must still be sent. Do not gate the DOI call on upsert success.

**Unknown Brevo custom attributes cause API errors.**
If the attributes sent in the payload include keys that do not exist in the Brevo account (`Contacts → Settings → Attributes`), Brevo returns a 400 error. The backend should implement a retry strategy: first try with full attributes, then retry with stripped marketing attributes, then retry with empty attributes. Never surface Brevo attribute errors to the user.

**Rate limiting.**
45-second cooldown keyed by normalized email + request IP hash. This prevents form spam and duplicate submissions. The frontend should not retry automatically on `rate_limited` — show the message and let the user wait.

---

## 7. Email Template Note

The DOI confirmation email in Blackwork was redesigned to match the Blackwork visual identity (custom HTML, styled button, brand typography).

**Functional requirement:** The confirmation button inside the email must use the native Brevo DOI link. This is not a URL you construct — it is a system link that Brevo injects automatically into templates that use the DOI flow. In Brevo's template editor, the confirmation link is typically available as a system variable or via the dedicated DOI button block.

**Do not use a fake placeholder** (such as a static `{{ double_optin }}` variable or a custom redirect URL that does not go through Brevo's confirmation mechanism). Custom HTML buttons can look correct visually while silently breaking the confirmation flow if the link is wrong.

**The React app does not need to recreate the email.** The template lives entirely in Brevo. The React backend only needs to trigger the DOI flow by calling `/contacts/doubleOptinConfirmation` with the correct `templateId`. Brevo handles email rendering and delivery.

---

## 8. React App Requirements

### Form fields

```
name      — optional, text input
email     — required, text input, normalized before submit
privacy   — required, checkbox, must be checked to submit
```

### Submit behavior

1. Normalize email client-side before send
2. Validate locally (empty, invalid format, missing consent)
3. Set busy state (disable button, show loading message)
4. POST to backend
5. Parse JSON response
6. On success: show success message, reset form (except on `already_subscribed`)
7. On error: show error message, mark relevant field as `aria-invalid`
8. Always clear busy state in finally block

### Message states

All messages come from configuration (not hardcoded strings). Required message keys:

| Key | Default fallback |
|---|---|
| `emptyEmail` | Please enter your email address. |
| `invalidEmail` | Please enter a valid email address. |
| `missingConsent` | You must agree to the Privacy Policy. |
| `loading` | Submitting... |
| `success` | Thank you! Check your inbox to confirm your subscription. |
| `alreadySubscribed` | You are already subscribed. |
| `rateLimited` | Please wait a moment before trying again. |
| `genericFailure` | Something went wrong. Please try again. |
| `networkFailure` | Connection error. Please try again. |

### API payload (backend → Brevo)

Attributes to send per subscriber:

| Brevo attribute key | Source | Notes |
|---|---|---|
| `FIRSTNAME` | parsed from `name` field | Optional — only if name provided |
| `LASTNAME` | parsed from `name` field | Optional — only if name provided |
| `SOURCE` | config value (e.g. `react_app`) | Identifies which channel this came from |
| `CONSENT_SOURCE` | same as SOURCE | |
| `CONSENT_AT` | server timestamp at submit time | ISO8601 format |
| `CONSENT_STATUS` | hardcoded `granted` | Consent was explicit |
| `BW_ORIGIN_SYSTEM` | config value (e.g. `app`) | Distinguishes app vs. WordPress |
| `BW_ENV` | env variable (`production` / `staging`) | |

All custom attributes must exist in the Brevo account before sending. Create them at `Contacts → Settings → Attributes`.

### Configuration fields

The React app's backend configuration layer must expose:

```
BREVO_API_KEY              — Brevo API key (server-side only)
BREVO_LIST_ID              — Marketing list ID (numeric, e.g. 10)
BREVO_UNCONFIRMED_LIST_ID  — Unconfirmed list ID (numeric, e.g. 11)
BREVO_DOI_TEMPLATE_ID      — DOI email template ID (numeric)
BREVO_DOI_REDIRECT_URL     — URL subscriber lands on after confirmation
BREVO_SENDER_NAME          — Sender display name (e.g. Blackwork)
BREVO_SENDER_EMAIL         — Sender address (must be authenticated domain)
BREVO_SOURCE_KEY           — Source tag sent to Brevo (e.g. react_app)
BREVO_ENV                  — Environment tag (production / staging)
SUBSCRIBE_COOLDOWN_SECONDS — Rate limit window (default: 45)
CONSENT_REQUIRED           — Boolean, whether privacy checkbox is required
PRIVACY_URL                — URL to the privacy policy page
```

### Deterministic list handling

- If `BREVO_UNCONFIRMED_LIST_ID` is `0` or unset: skip the upsert step entirely.
- If `BREVO_LIST_ID` is `0` or unset: reject the submission before calling Brevo.
- Never fall back to a hardcoded list ID.

### Welcome flow trigger assumptions

The welcome email automation in Brevo is triggered by `added to Blackwork – Marketing (#10)`. This happens automatically when the subscriber clicks the confirmation link. The React app does not trigger this directly — it only needs to ensure the DOI flow completes correctly so Brevo fires the automation on confirmation.

---

## 9. Recommended File Structure

```
src/
├── components/
│   └── SubscribeForm/
│       ├── SubscribeForm.tsx          — Form UI, validation, state management
│       ├── SubscribeForm.module.css   — Styles
│       └── useSubscribe.ts            — Submit logic hook
│
├── services/
│   └── subscribeApi.ts                — POST to backend, parse response, typed
│
├── constants/
│   └── subscribeMessages.ts           — All user-facing message strings (or i18n keys)
│
└── app/
    └── api/
        └── subscribe/
            └── route.ts               — Backend route (Next.js) or equivalent

server/
├── lib/
│   └── brevo/
│       ├── brevoClient.ts             — Brevo API adapter (upsert, DOI)
│       ├── subscriptionService.ts     — Orchestration: validate → cooldown → lookup → upsert → DOI
│       └── attributeBuilder.ts        — Build the Brevo attributes payload
│
└── config/
    └── mailMarketing.ts               — Read all env vars, validate presence, export typed config
```

**Responsibilities:**

| Layer | Responsibility |
|---|---|
| `SubscribeForm` | Render form, manage local UI state, call `useSubscribe` |
| `useSubscribe` | Client-side validation, busy state, call `subscribeApi`, handle response codes |
| `subscribeApi` | HTTP POST, parse JSON, normalize to typed response |
| `subscribeMessages` | Single source for all user-facing copy |
| `route.ts` | Request parsing, server-side validation, call `subscriptionService`, return response |
| `subscriptionService` | Cooldown gate, contact lookup, upsert, DOI call — in order |
| `brevoClient` | Thin adapter over Brevo API — one function per endpoint |
| `attributeBuilder` | Build the attributes object from subscriber data + config |
| `mailMarketing config` | Read env vars once at startup, fail loudly if required values are missing |

---

## 10. Final Checklist

Use this checklist to verify the system is fully connected and working end-to-end.

### Configuration
- [ ] `BREVO_API_KEY` set and server-side only (not in client bundle)
- [ ] `BREVO_LIST_ID` set to `10` (Marketing)
- [ ] `BREVO_UNCONFIRMED_LIST_ID` set to `11` (Unconfirmed)
- [ ] `BREVO_DOI_TEMPLATE_ID` set to the correct Brevo template ID
- [ ] `BREVO_DOI_REDIRECT_URL` points to a real confirmation page on the site
- [ ] `BREVO_SENDER_EMAIL` is `hello@blackwork.pro` (authenticated domain)
- [ ] `BREVO_SENDER_NAME` is set
- [ ] Custom Brevo attributes created in `Contacts → Settings → Attributes`: `SOURCE`, `CONSENT_SOURCE`, `CONSENT_AT`, `CONSENT_STATUS`, `BW_ORIGIN_SYSTEM`, `BW_ENV`, `FIRSTNAME`, `LASTNAME`

### Brevo panel
- [ ] List `#10 Blackwork – Marketing` exists
- [ ] List `#11 Blackwork – Unconfirmed` exists
- [ ] DOI template contains the native Brevo confirmation link (not a fake placeholder)
- [ ] Brevo Automation configured: trigger = added to Marketing list (#10) → remove from Unconfirmed list (#11)
- [ ] Brevo Automation configured: trigger = added to Marketing list (#10) → send Welcome email

### Form behavior
- [ ] Empty email: shows error, marks field invalid
- [ ] Invalid email format: shows error, marks field invalid
- [ ] Missing consent: shows error, marks checkbox invalid
- [ ] Button disabled during submit, re-enabled after
- [ ] No duplicate submits possible
- [ ] Form resets on success (but not on `already_subscribed`)

### End-to-end flow
- [ ] Submit form → contact appears in Brevo under Unconfirmed list (#11) immediately
- [ ] Submit form → DOI confirmation email is received in inbox
- [ ] Click confirmation link → contact appears in Brevo under Marketing list (#10)
- [ ] Click confirmation link → contact is removed from Unconfirmed list (#11) by automation
- [ ] Click confirmation link → welcome email arrives
- [ ] `already_subscribed` response returned when submitting with an email already in Marketing list
- [ ] `rate_limited` response returned on rapid re-submit within 45 seconds

### Security
- [ ] Brevo API key not present in browser network requests
- [ ] Raw Brevo error messages not present in API responses
- [ ] Server-side consent validation independent of client-side flag
- [ ] Cooldown gate active (test with rapid repeated submits)
