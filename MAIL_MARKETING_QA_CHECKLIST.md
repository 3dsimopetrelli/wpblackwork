# Mail Marketing -- QA Checklist

Blackwork Site Plugin\
Brevo Integration -- Checkout & Admin Refactor

------------------------------------------------------------------------

## Pre-flight

-   [ ] Backup database (or export relevant options)
-   [ ] Enable WP_DEBUG_LOG (staging/local recommended)
-   [ ] Confirm valid Brevo API key
-   [ ] Ensure test list exists in Brevo

------------------------------------------------------------------------

## 1. Admin UI -- Structure

-   [ ] New submenu exists: **Blackwork Site → Mail Marketing**
-   [ ] Tabs visible: **General** and **Checkout**
-   [ ] Old page "Checkout → Subscribe":
    -   [ ] Removed OR
    -   [ ] Displays notice + redirect to new page
-   [ ] No duplicated configuration pages exist

------------------------------------------------------------------------

## 2. Settings Migration

-   [ ] Previous values migrated automatically
-   [ ] New options exist in DB:
    -   `bw_mail_marketing_general_settings`
    -   `bw_mail_marketing_checkout_settings`
-   [ ] Changing values persists correctly after refresh
-   [ ] Old option `bw_checkout_subscribe_settings` not used as primary
    source

------------------------------------------------------------------------

## 3. General Tab -- Brevo Connection

### Test Connection

-   [ ] Button works with nonce & capability check
-   [ ] Invalid API key shows proper error
-   [ ] Valid API key returns success message
-   [ ] No PHP warnings or JS errors

### API Base URL

-   [ ] Not editable (hardcoded or readonly)

### List Selector

-   [ ] Dropdown loads lists (if implemented)
-   [ ] Selected list saves correctly

------------------------------------------------------------------------

## 4. Checkout Tab -- UI & Behavior

-   [ ] Enable newsletter checkbox toggle works
-   [ ] Default checked behaves correctly
-   [ ] Label and privacy text render properly
-   [ ] Subscribe timing select exists (paid / created)
-   [ ] Placement compatible with Checkout Fields

------------------------------------------------------------------------

## 5. Checkout -- Consent Save

### Checkbox NOT checked

-   [ ] `_bw_subscribe_newsletter` = 0 or absent
-   [ ] `_bw_brevo_subscribed` = skipped or empty
-   [ ] No Brevo API call made

### Checkbox checked

-   [ ] `_bw_subscribe_newsletter` = 1
-   [ ] `_bw_subscribe_consent_at` saved
-   [ ] `_bw_subscribe_consent_source` correct
-   [ ] `_bw_brevo_subscribed` = pending (if timing=paid)

------------------------------------------------------------------------

## 6. Paid Trigger

-   [ ] On order status processing/completed:
    -   [ ] Contact added to Brevo list
    -   [ ] `_bw_brevo_subscribed` updated to subscribed
    -   [ ] Log written under `bw-brevo`

------------------------------------------------------------------------

## 7. Double Opt-in (If Enabled)

-   [ ] DOI email sent
-   [ ] Redirect works
-   [ ] Contact appears in list after confirmation

------------------------------------------------------------------------

## 8. Duplicate Email Handling

-   [ ] No duplicate contacts created
-   [ ] Status logged correctly

------------------------------------------------------------------------

## 9. Unsubscribed / Blocklisted Handling

-   [ ] No automatic resubscribe
-   [ ] Status marked skipped
-   [ ] Log contains correct reason

------------------------------------------------------------------------

## 10. Security & Validation

-   [ ] AJAX protected with nonce
-   [ ] Capability checks in place
-   [ ] Inputs sanitized properly

------------------------------------------------------------------------

## 11. Regression Checks

-   [ ] Checkout still completes normally
-   [ ] No JS errors on frontend
-   [ ] No impact on Supabase login flow
-   [ ] No impact on cart popup

------------------------------------------------------------------------

## Quick Smoke Test (2-Minute Check)

1.  Test connection works ✅
2.  Place guest order with opt-in ✅
3.  Set order to paid ✅
4.  Contact appears in Brevo list ✅
5.  Log entry created ✅

------------------------------------------------------------------------

End of Checklist
