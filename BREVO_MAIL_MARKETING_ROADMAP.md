# 🚀 Blackwork -- Brevo Mail Marketing Roadmap

*Last updated: 2026-02-24*

Legend:

-   🟩 **Brevo Panel**
-   🟦 **WordPress Plugin (Blackwork Site)**
-   ✅ Completed
-   ⏳ In Progress / To Implement
-   🔜 Future Phase

------------------------------------------------------------------------

# PHASE 0 --- Foundations (Infrastructure)

## 🟩 Brevo

-   ✅ Domain authenticated (SPF / DKIM / DMARC)
-   ✅ Sender verified (hello@blackwork.pro)
-   ✅ Folder: Blackwork -- CRM
-   ✅ List: Blackwork -- Marketing
-   ✅ List: Blackwork -- Unconfirmed

## 🟦 WordPress Plugin

-   ✅ Mail Marketing section (General + Checkout)
-   ✅ API Key connected + Test Connection working
-   ✅ Checkout opt-in (classic checkout)
-   ✅ Brevo sync on paid hook
-   ✅ GDPR consent gating (no subscription without consent)
-   ✅ Order Admin "Newsletter Status" panel
-   ✅ Orders list column (Newsletter)
-   ✅ User profile panel (Brevo status)

------------------------------------------------------------------------

# PHASE 1 --- Brevo Data Model (Structured Attributes)

## 🟩 Brevo -- Required Contact Attributes

⏳ Create / verify these attributes in Brevo:

-   SOURCE (text)
-   CONSENT_SOURCE (text)
-   CONSENT_STATUS (text: granted / pending / revoked)
-   CONSENT_AT (text or date)
-   BW_ORIGIN_SYSTEM (text: wp / app)
-   BW_ENV (text: production / staging)

Optional (recommended): - LAST_ORDER_ID (number/text) - LAST_ORDER_AT
(date/text) - CUSTOMER_STATUS (lead / customer / repeat_customer)

## 🟦 WordPress Plugin

⏳ Send structured attributes on every upsert (checkout + retry + bulk).
⏳ Standardize source taxonomy: - checkout - footer - popup -
coming_soon - my_account - supabase_google - supabase_facebook -
supabase_magic_link - app_stripe

------------------------------------------------------------------------

# PHASE 2 --- Automations (Brevo)

## 🟩 Brevo

⏳ Welcome workflow (trigger: added to Marketing list) ⏳ Branch logic
based on CONSENT_SOURCE ⏳ Double opt-in workflow for Unconfirmed list
🔜 Post-purchase automation

## 🟦 WordPress Plugin

🔜 Optional post-order attribute updates (orders_count, status)

------------------------------------------------------------------------

# PHASE 3 --- New Subscription Channels

## 🟩 Brevo

⏳ Double opt-in template (popup/footer/coming soon) ⏳ Confirmation
redirect page

## 🟦 WordPress Plugin

🔜 Coming Soon migration to central subscribe service 🔜 Footer
newsletter form 🔜 Popup newsletter form 🔜 My Account marketing toggle
🔜 Supabase login flow with explicit opt-in prompt

------------------------------------------------------------------------

# PHASE 4 --- Compliance & Preferences

## 🟩 Brevo

⏳ Verify unsubscribe handling 🔜 Optional Preference Center

## 🟦 WordPress Plugin

🔜 My Account "Email Preferences" section 🔜 Sync unsubscribe status (if
needed)

------------------------------------------------------------------------

# PHASE 5 --- Admin Tools & Operations

## 🟩 Brevo

🔜 Saved segments (by SOURCE, CONSENT_STATUS, CUSTOMER_STATUS)

## 🟦 WordPress Plugin

⏳ Orders filter: Newsletter status ⏳ Orders filter: Source ⏳ Bulk
action: Resync to Brevo ⏳ CSV Export (Email + Status + Source +
Consent) 🔜 Bulk read-only Brevo check

------------------------------------------------------------------------

# PHASE 6 --- Monitoring & Optimization

## 🟩 Brevo

🔜 Deliverability monitoring 🔜 Bounce / complaint analysis

## 🟦 WordPress Plugin

🔜 Standardized reason codes in logs 🔜 QA checklist for each new
channel

------------------------------------------------------------------------

# Current Status Summary

Checkout opt-in + Brevo sync is fully operational and GDPR-safe.

Next strategic priority: → Complete Brevo attribute model + Welcome
automation.
