# BlackWork Site - Complete Technical Documentation

**Version:** 1.0
**Last Updated:** January 2026
**Plugin Path:** `wp-content/plugins/wpblackwork/`

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Site Settings Backend](#2-site-settings-backend)
3. [Supabase Authentication System](#3-supabase-authentication-system)
4. [Authentication Methods](#4-authentication-methods)
5. [WooCommerce Checkout System](#5-woocommerce-checkout-system)
6. [Stripe Payment Gateway](#6-stripe-payment-gateway)
7. [My Account System](#7-my-account-system)
8. [Configuration Options Reference](#8-configuration-options-reference)
9. [AJAX Endpoints Reference](#9-ajax-endpoints-reference)
10. [Implementation Guide for New Features](#10-implementation-guide-for-new-features)
11. [Data Flow Diagrams](#11-data-flow-diagrams)
12. [Security Considerations](#12-security-considerations)

---

## 1. Architecture Overview

### 1.1 Plugin Structure

```
wpblackwork/
├── admin/
│   ├── class-blackwork-site-settings.php    # Main settings page (6000+ lines)
│   ├── css/
│   │   ├── blackwork-site-settings.css      # Admin styles
│   │   └── blackwork-site-menu.css          # Menu styles
│   └── js/
│       ├── bw-redirects.js                  # Redirect manager
│       └── bw-checkout-subscribe.js         # Brevo test connection
├── assets/
│   ├── css/
│   │   ├── bw-account-page.css              # Login page styles
│   │   ├── bw-my-account.css                # Dashboard styles
│   │   ├── bw-checkout.css                  # Checkout styles (97KB)
│   │   ├── bw-payment-methods.css           # Payment accordion
│   │   └── bw-checkout-notices.css          # Checkout notices
│   └── js/
│       ├── bw-account-page.js               # Login page handler (1367 lines)
│       ├── bw-my-account.js                 # Dashboard handler (549 lines)
│       ├── bw-supabase-bridge.js            # OAuth token bridge (387 lines)
│       ├── bw-checkout.js                   # Checkout handler (99KB)
│       ├── bw-payment-methods.js            # Payment accordion (310 lines)
│       ├── bw-google-pay.js                 # Google Pay handler
│       └── bw-stripe-upe-cleaner.js         # Stripe UPE customization
├── includes/
│   ├── woocommerce-overrides/
│   │   ├── class-bw-supabase-auth.php       # Supabase auth (1479 lines)
│   │   ├── class-bw-social-login.php        # OAuth provider (548 lines)
│   │   ├── class-bw-my-account.php          # My Account logic (421 lines)
│   │   ├── class-bw-google-pay-gateway.php  # Google Pay gateway
│   │   └── class-bw-product-card-renderer.php
│   ├── admin/
│   │   ├── checkout-fields/
│   │   │   ├── class-bw-checkout-fields-admin.php
│   │   │   └── class-bw-checkout-fields-frontend.php
│   │   └── checkout-subscribe/
│   │       ├── class-bw-checkout-subscribe-admin.php
│   │       └── class-bw-checkout-subscribe-frontend.php
│   └── widgets/                              # Elementor widgets
├── woocommerce/
│   ├── woocommerce-init.php                 # WooCommerce customizations (1558 lines)
│   └── templates/
│       ├── checkout/
│       │   ├── form-checkout.php            # Main checkout template
│       │   ├── form-billing.php             # Billing form
│       │   ├── form-shipping.php            # Shipping form
│       │   ├── payment.php                  # Payment accordion
│       │   └── review-order.php             # Order summary
│       └── myaccount/
│           ├── my-account.php               # Account wrapper
│           ├── form-login.php               # Login page (236 lines)
│           ├── navigation.php               # Dashboard nav
│           ├── dashboard.php                # Dashboard content
│           ├── form-edit-account.php        # Settings tabs
│           └── set-password.php             # Onboarding password
├── cart-popup/                              # Cart popup submodule
├── BW_coming_soon/                          # Coming soon submodule
└── bw-main-elementor-widgets.php            # Main plugin file
```

### 1.2 Core Dependencies

| Dependency | Purpose |
|------------|---------|
| WordPress | Core CMS |
| WooCommerce | E-commerce functionality |
| Elementor | Page builder integration |
| Supabase JS SDK | Authentication client |
| Stripe JS | Payment processing |
| Google Maps API | Address autocomplete |
| Brevo API | Newsletter subscription |

### 1.3 Initialization Flow

```
plugins_loaded (priority 5)
    ↓
bw_mew_initialize_woocommerce_overrides()
    ├─ Load class-bw-supabase-auth.php
    ├─ Load class-bw-my-account.php
    ├─ Load class-bw-social-login.php
    ├─ Load class-bw-google-pay-gateway.php
    └─ Register filters & actions
    ↓
init (priority 5)
    ├─ Register rewrite endpoints
    └─ Register widget assets
    ↓
wp_enqueue_scripts (priority 20-30)
    └─ Enqueue page-specific assets
```

---

## 2. Site Settings Backend

### 2.1 Settings Page Structure

**Main File:** `admin/class-blackwork-site-settings.php`

**Admin Menu Location:** Blackwork Site → Settings

**Top-Level Tabs:**
| Tab Key | Label | Description |
|---------|-------|-------------|
| `cart-popup` | Cart Pop-up | Side-sliding cart configuration |
| `bw-coming-soon` | BW Coming Soon | Coming soon page with video |
| `account-page` | Account Page | Login page design + Supabase settings |
| `my-account-page` | My Account Page | Dashboard customization |
| `checkout` | Checkout | Checkout layout and integrations |
| `redirect` | Redirect | URL redirect management |
| `import-product` | Import Product | Product import tools |
| `loading` | Loading | Loading screen settings |
| `google-pay` | Google Pay | Google Pay gateway config |

### 2.2 Account Page Tab (Sub-tabs)

**Design Tab:**
- Login Image (cover)
- Logo URL and dimensions
- Logo padding (top/bottom)
- Login title and subtitle
- Show social login buttons toggle

**Technical Settings Tab:**
Contains ALL Supabase configuration fields (see Section 3.3)

### 2.3 Checkout Tab (Sub-tabs)

| Sub-tab | Key | Description |
|---------|-----|-------------|
| Style | `style` | Logo, colors, widths, padding |
| Supabase Provider | `supabase` | Guest checkout provisioning |
| Checkout Fields | `fields` | Field visibility and order |
| Subscribe | `subscribe` | Brevo newsletter integration |
| Google Maps | `google-maps` | Address autocomplete |
| Footer Cleanup | `footer` | Legal text and policies |

### 2.4 Settings Save Mechanism

**Form Handling:**
```php
// Account Page save
if (isset($_POST['bw_account_page_submit'])) {
    check_admin_referer('bw_account_page_save', 'bw_account_page_nonce');
    // Process each option with appropriate sanitization
    update_option('bw_account_login_image', esc_url_raw($_POST['bw_account_login_image']));
    // ... etc
}
```

**Sanitization Functions Used:**
- `esc_url_raw()` - URLs
- `sanitize_text_field()` - Single-line text
- `sanitize_textarea_field()` - Multi-line text
- `absint()` - Integers
- `sanitize_hex_color()` - Colors

---

## 3. Supabase Authentication System

### 3.1 Core Files

| File | Purpose | Lines |
|------|---------|-------|
| `class-bw-supabase-auth.php` | Main auth handler | 1479 |
| `bw-supabase-bridge.js` | OAuth token bridge | 387 |
| `bw-account-page.js` | Login form handler | 1367 |

### 3.2 Supabase API Endpoints Used

**Public Endpoints (Anon Key):**
```
POST /auth/v1/token?grant_type=password     # Password login
POST /auth/v1/otp                            # Request OTP
POST /auth/v1/verify                         # Verify OTP
GET  /auth/v1/user                           # Get user profile
PUT  /auth/v1/user                           # Update user profile
POST /auth/v1/token?grant_type=pkce          # PKCE code exchange
```

**Admin Endpoints (Service Role Key):**
```
POST /auth/v1/admin/invite                   # Send user invite
GET  /auth/v1/admin/users                    # List users
```

### 3.3 Configuration Options (wp_options)

**Core Connection:**
```php
bw_supabase_project_url       // Supabase project URL (e.g., https://xxx.supabase.co)
bw_supabase_anon_key          // Anon/public key for frontend
bw_supabase_service_role_key  // Service role key for admin operations
```

**Authentication Modes:**
```php
bw_supabase_auth_mode              // 'password', 'otp', 'magic_link'
bw_supabase_login_mode             // 'native' or 'iframe'
bw_supabase_registration_mode      // 'R2', 'R1', 'R0'
bw_supabase_with_plugins           // Enable OIDC plugin integration
```

**Feature Toggles:**
```php
bw_supabase_magic_link_enabled       // Enable magic link/OTP
bw_supabase_login_password_enabled   // Enable password login
bw_supabase_otp_allow_signup         // Allow signup via OTP
bw_supabase_create_wp_users          // Auto-create WP users
bw_supabase_enable_wp_user_linking   // Link Supabase to WP users
```

**OAuth Provider Toggles:**
```php
bw_supabase_oauth_google_enabled     // Enable Google OAuth
bw_supabase_oauth_facebook_enabled   // Enable Facebook OAuth
bw_supabase_oauth_apple_enabled      // Enable Apple OAuth
```

**OAuth Provider Credentials:**
```php
// Google
bw_supabase_google_client_id
bw_supabase_google_client_secret
bw_supabase_google_redirect_url
bw_supabase_google_scopes
bw_supabase_google_prompt

// Facebook
bw_supabase_facebook_app_id
bw_supabase_facebook_app_secret
bw_supabase_facebook_redirect_url
bw_supabase_facebook_scopes

// Apple
bw_supabase_apple_client_id
bw_supabase_apple_team_id
bw_supabase_apple_key_id
bw_supabase_apple_private_key
bw_supabase_apple_redirect_url
```

**Redirect URLs:**
```php
bw_supabase_magic_link_redirect_url      // After OTP confirmation
bw_supabase_oauth_redirect_url           // After OAuth callback
bw_supabase_signup_redirect_url          // After signup confirmation
bw_supabase_email_confirm_redirect_url   // After email confirmation
bw_supabase_provider_signup_url          // Provider signup URL
bw_supabase_provider_reset_url           // Password reset URL
```

**Session Management:**
```php
bw_supabase_session_storage         // 'cookie' or 'usermeta'
bw_supabase_jwt_cookie_name         // Cookie base name (default: 'bw_supabase_session')
bw_supabase_auto_login_after_confirm
```

**Checkout Provisioning:**
```php
bw_supabase_checkout_provision_enabled   // Enable guest user invites
bw_supabase_invite_redirect_url          // Invite redirect (default: /my-account/set-password/)
```

**Debug:**
```php
bw_supabase_debug_log                    // Enable debug logging
```

### 3.4 Session Storage

**Cookie-Based (Default):**
```php
Cookie: {cookie_name}_access   // Access token (1 hour)
Cookie: {cookie_name}_refresh  // Refresh token (30 days)

Flags: httponly, secure (if HTTPS), SameSite=Lax
```

**UserMeta-Based:**
```php
User Meta: bw_supabase_access_token
User Meta: bw_supabase_refresh_token
User Meta: bw_supabase_expires_at
```

### 3.5 User Meta Keys

| Meta Key | Purpose |
|----------|---------|
| `bw_supabase_onboarded` | 0=needs password, 1=complete |
| `bw_supabase_invited` | 1=invited via checkout |
| `bw_supabase_pending_email` | Email change pending |
| `bw_supabase_user_id` | Supabase UUID |
| `bw_supabase_invited_at` | Invite timestamp |
| `bw_supabase_invite_resend_count` | Resend attempts |

---

## 4. Authentication Methods

### 4.1 Magic Link / OTP

**Flow Diagram:**
```
┌─────────────────┐
│  User enters    │
│     email       │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Check if email  │ → AJAX: bw_supabase_email_exists
│    exists       │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Request OTP    │ → Supabase: POST /auth/v1/otp
│                 │   {email, should_create_user}
└────────┬────────┘
         ↓
┌─────────────────┐
│ User receives   │
│  6-digit code   │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Enter OTP in   │
│   6 inputs      │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Verify OTP     │ → Supabase: POST /auth/v1/verify
│                 │   {email, token, type: 'email'}
└────────┬────────┘
         ↓
┌─────────────────┐
│ Get tokens      │ → access_token, refresh_token
└────────┬────────┘
         ↓
┌─────────────────┐
│ Bridge to WP    │ → AJAX: bw_supabase_token_login
└────────┬────────┘
         ↓
┌─────────────────┐
│ Create/link     │ → wp_set_auth_cookie()
│   WP user       │
└────────┬────────┘
         ↓
┌─────────────────┐
│   Redirect to   │
│   /my-account/  │
└─────────────────┘
```

**Key Functions:**
- `requestOtp(email, shouldCreateUser)` - JS function in bw-account-page.js
- `verifyOtp(email, code)` - JS function for code verification
- `bridgeSupabaseSession()` - Bridges Supabase tokens to WordPress

### 4.2 Password Login

**Flow Diagram:**
```
┌─────────────────┐
│ User enters     │
│ email+password  │
└────────┬────────┘
         ↓
┌─────────────────┐
│   AJAX POST     │ → bw_supabase_login
└────────┬────────┘
         ↓
┌─────────────────┐
│ Backend calls   │ → POST /auth/v1/token?grant_type=password
│   Supabase      │   {email, password}
└────────┬────────┘
         ↓
┌─────────────────┐
│ Store session   │ → bw_mew_supabase_store_session()
│ (cookie/meta)   │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Set WP cookie   │ → wp_set_auth_cookie($user_id)
└────────┬────────┘
         ↓
┌─────────────────┐
│   Redirect to   │
│   /my-account/  │
└─────────────────┘
```

**Backend Handler:** `bw_mew_handle_supabase_login()` (Line 470)

### 4.3 OAuth (Google/Facebook/Apple)

**Flow Diagram:**
```
┌─────────────────┐
│ User clicks     │
│ "Continue with  │
│    Google"      │
└────────┬────────┘
         ↓
┌─────────────────┐
│ JS: signInWith  │ → supabase.auth.signInWithOAuth({
│    OAuth()      │      provider: 'google',
│                 │      options: {redirectTo: ...}
│                 │   })
└────────┬────────┘
         ↓
┌─────────────────┐
│ Redirect to     │
│ Google OAuth    │
│ consent screen  │
└────────┬────────┘
         ↓
┌─────────────────┐
│ User authorizes │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Google returns  │ → ?code=XXX&state=YYY
│ auth code       │
└────────┬────────┘
         ↓
┌─────────────────┐
│ bw-supabase-    │ → Detects ?code param
│ bridge.js       │
└────────┬────────┘
         ↓
┌─────────────────┐
│ PKCE Exchange   │ → POST /auth/v1/token?grant_type=pkce
│ (or SDK)        │   OR exchangeCodeForSession(code)
└────────┬────────┘
         ↓
┌─────────────────┐
│ Bridge tokens   │ → AJAX: bw_supabase_token_login
│ to WordPress    │
└────────┬────────┘
         ↓
┌─────────────────┐
│   Redirect to   │
│   /my-account/  │
└─────────────────┘
```

**JavaScript Handler:** `bw-supabase-bridge.js` (Lines 337-383)

### 4.4 Guest Checkout Provisioning (Invite)

**Flow Diagram:**
```
┌─────────────────┐
│ Guest places    │
│ order           │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Order status    │ → woocommerce_order_status_processing
│ changes         │   woocommerce_order_status_completed
└────────┬────────┘
         ↓
┌─────────────────┐
│ Check option    │ → bw_supabase_checkout_provision_enabled
└────────┬────────┘
         ↓
┌─────────────────┐
│ Create WP user  │ → Username from email prefix
│ (if needed)     │   Random 32-char password
└────────┬────────┘
         ↓
┌─────────────────┐
│ Send Supabase   │ → POST /auth/v1/admin/invite
│ invite          │   Headers: Authorization: Bearer {service_key}
│                 │   Body: {email, redirect_to, data}
└────────┬────────┘
         ↓
┌─────────────────┐
│ User receives   │ → Link contains: #access_token=...
│ email           │   &refresh_token=...&type=invite
└────────┬────────┘
         ↓
┌─────────────────┐
│ User clicks     │ → Redirected to /my-account/set-password/
│ invite link     │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Extract tokens  │ → bw-supabase-bridge.js parses hash
│ from URL hash   │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Bridge to WP    │ → bw_supabase_token_login with type=invite
└────────┬────────┘
         ↓
┌─────────────────┐
│ User creates    │ → AJAX: bw_supabase_update_password
│ password        │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Set onboarded=1 │
│ Redirect to     │
│ /my-account/    │
└─────────────────┘
```

**Backend Handler:** `bw_mew_handle_supabase_checkout_invite()` (Line 998)

### 4.5 Password Requirements

```
- Minimum 8 characters
- At least 1 uppercase letter (A-Z)
- At least 1 number (0-9) OR special character (!@#$%, etc.)
```

**Validation Function:**
```php
// PHP (class-bw-supabase-auth.php:869)
function bw_mew_supabase_password_meets_requirements($password) {
    if (strlen($password) < 8) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[0-9]|[^A-Za-z0-9]/', $password)) return false;
    return true;
}
```

```javascript
// JS (bw-account-page.js:380-407)
const rules = [
    { id: 'length', test: v => v.length >= 8 },
    { id: 'upper', test: v => /[A-Z]/.test(v) },
    { id: 'number', test: v => /[0-9]|[^A-Za-z0-9]/.test(v) }
];
```

---

## 5. WooCommerce Checkout System

### 5.1 Template Structure

**Template Override Hierarchy:**
```php
// woocommerce-init.php:104-113
add_filter('woocommerce_locate_template', 'bw_mew_locate_template', 1, 3);

function bw_mew_locate_template($template, $template_name, $template_path) {
    $plugin_path = BW_MEW_PATH . 'woocommerce/templates/';
    if (file_exists($plugin_path . $template_name)) {
        return $plugin_path . $template_name;
    }
    return $template;
}
```

**Key Templates:**

| Template | Purpose |
|----------|---------|
| `form-checkout.php` | Main two-column layout |
| `form-billing.php` | Billing fields with newsletter |
| `form-shipping.php` | Shipping address fields |
| `payment.php` | Payment methods accordion |
| `review-order.php` | Order summary with quantity controls |

### 5.2 Two-Column Layout

**CSS Variables (form-checkout.php):**
```css
--bw-checkout-left-col: 62%;           /* Left column width */
--bw-checkout-right-col: 38%;          /* Right column width */
--bw-checkout-page-bg: #ffffff;        /* Page background */
--bw-checkout-grid-bg: #ffffff;        /* Grid background */
--bw-checkout-left-bg: #ffffff;        /* Left column bg */
--bw-checkout-right-bg: transparent;   /* Right column bg */
--bw-checkout-border-color: #262626;   /* Column separator */
--bw-checkout-right-sticky-top: 20px;  /* Sticky offset */
```

**Grid Structure:**
```html
<div class="bw-checkout-wrapper">
    <div class="bw-checkout-grid">
        <div class="bw-checkout-left">
            <!-- Logo, Contact/Email, Billing, Shipping, Payment, Legal, Footer -->
        </div>
        <div class="bw-checkout-right">
            <!-- Order Summary (sticky) -->
        </div>
    </div>
</div>
```

### 5.3 Checkout Fields System

**Settings Storage:** `bw_checkout_fields_settings` option

**Structure:**
```php
[
    'version' => 1,
    'billing' => [
        'billing_first_name' => [
            'enabled' => true,
            'priority' => 10,
            'width' => 'half',  // 'half' or 'full'
            'label' => 'Custom Label',
            'required' => true
        ],
        // ... more fields
    ],
    'shipping' => [...],
    'order' => [...],
    'account' => [...],
    'section_headings' => [
        'hide_billing_details' => 0,
        'hide_additional_info' => 0,
        'address_heading_text' => 'Delivery'
    ]
]
```

**Filter:** `woocommerce_checkout_fields`

### 5.4 Newsletter Integration (Brevo)

**Settings:** `bw_checkout_subscribe_settings` option

**Fields:**
| Setting | Type | Purpose |
|---------|------|---------|
| `enabled` | bool | Show newsletter checkbox |
| `default_checked` | bool | Pre-check checkbox |
| `label_text` | string | Checkbox label |
| `privacy_text` | string | Disclaimer text |
| `api_key` | string | Brevo API key |
| `api_base` | string | API base URL |
| `list_id` | int | Brevo list ID |
| `double_optin_enabled` | bool | Send confirmation email |
| `double_optin_template_id` | int | Email template ID |
| `double_optin_redirect_url` | string | Confirmation redirect |
| `sender_name` | string | Email sender name |
| `sender_email` | string | Email sender address |
| `subscribe_timing` | string | 'created' or 'paid' |

**Subscription Hooks:**
- `woocommerce_checkout_order_processed` - When order created
- `woocommerce_order_status_processing` - When payment received
- `woocommerce_order_status_completed` - When order completed

### 5.5 Google Maps Autocomplete

**Settings:**
```php
bw_google_maps_enabled           // '0' or '1'
bw_google_maps_api_key           // Google Maps API key
bw_google_maps_autofill          // Auto-fill city/postcode
bw_google_maps_restrict_country  // Restrict to store country
```

**Script Loading:**
```php
if ($enabled && $api_key) {
    wp_enqueue_script(
        'google-maps-places',
        'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places'
    );
}
```

### 5.6 Policy Modals

**Settings Pattern:** `bw_checkout_policy_{key}`

**Keys:** `refund`, `shipping`, `privacy`, `terms`, `contact`

**Structure per policy:**
```php
[
    'title' => 'Refund Policy',
    'subtitle' => 'Our commitment to you',
    'content' => '<p>Full HTML content...</p>'
]
```

---

## 6. Stripe Payment Gateway

### 6.1 Stripe Elements Customization

**Filter:** `wc_stripe_elements_options`

**Appearance Configuration:**
```php
[
    'theme' => 'flat',
    'variables' => [
        'colorPrimary' => '#000000',
        'colorBackground' => '#ffffff',
        'colorText' => '#1f2937',
        'colorDanger' => '#991b1b',
        'colorSuccess' => '#27ae60',
        'borderRadius' => '0px'
    ],
    'rules' => [
        '.Input' => [
            'border' => '1px solid #d1d5db',
            'padding' => '16px 18px',
            'borderRadius' => '8px'
        ]
    ]
]
```

### 6.2 Stripe UPE Customization

**Filter:** `wc_stripe_upe_params`

**Hidden Elements:**
- `.AccordionItemHeader` - Tab headers
- `.PaymentMethodHeader` - Method headers
- `.TabLabel`, `.TabIcon`, `.Tab` - Tab navigation

**Error Styling:**
```php
'.Error' => [
    'display' => 'flex',
    'flexDirection' => 'row',
    'alignItems' => 'flex-start',
    'gap' => '8px',
    'color' => '#991b1b'
]
```

### 6.3 Payment Methods Accordion

**Template:** `woocommerce/templates/checkout/payment.php`

**Structure:**
```html
<ul class="bw-payment-methods wc_payment_methods">
    <li class="bw-payment-method" data-gateway-id="stripe">
        <div class="bw-payment-method__header">
            <input type="radio" name="payment_method" value="stripe" />
            <label>Pay with Card</label>
        </div>
        <div class="bw-payment-method__content">
            <!-- Stripe Elements renders here -->
        </div>
    </li>
</ul>
```

**JavaScript Handler:** `bw-payment-methods.js`

**Key Functions:**
- `initPaymentMethodsAccordion()` - Setup accordion behavior
- `handlePaymentMethodChange()` - Handle selection
- `updatePlaceOrderButton()` - Update button text per gateway

### 6.4 Google Pay Integration

**Gateway Class:** `BW_Google_Pay_Gateway extends WC_Payment_Gateway`

**Gateway ID:** `bw_google_pay`

**Configuration:**
```php
bw_google_pay_enabled                 // '0' or '1'
bw_google_pay_test_mode               // '0' or '1'
bw_google_pay_test_publishable_key    // Test Stripe key
bw_google_pay_publishable_key         // Live Stripe key
```

**JavaScript Implementation:**
```javascript
// bw-google-pay.js
const paymentRequest = stripe.paymentRequest({
    country: 'IT',
    currency: 'eur',
    total: { label: 'Ordine BlackWork', amount: amountInCents },
    requestPayerName: true,
    requestPayerEmail: true,
    requestPayerPhone: true
});

paymentRequest.canMakePayment().then(result => {
    if (result) {
        // Show Google Pay button
    }
});
```

---

## 7. My Account System

### 7.1 Not Logged In State (Login Page)

**Template:** `woocommerce/templates/myaccount/form-login.php`

**Layout:**
- Left side: Cover image (50% width)
- Right side: Auth forms (50% width)
- Mobile: Full-width form only

**Authentication Screens:**
| Screen | Data Attribute | Purpose |
|--------|---------------|---------|
| Magic | `data-bw-screen="magic"` | Email input for OTP |
| Password | `data-bw-screen="password"` | Email + password |
| OTP | `data-bw-screen="otp"` | 6-digit code input |
| Create Password | `data-bw-screen="create-password"` | Set new password |

**Screen Navigation:**
```javascript
switchAuthScreen(target, options)
// targets: 'magic', 'password', 'otp', 'create-password'
```

### 7.2 Logged In State (Dashboard)

**Template:** `woocommerce/templates/myaccount/my-account.php`

**Layout:**
```css
.bw-account-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 32px;
}
```

**Navigation Items:**
| Endpoint | Label | Icon |
|----------|-------|------|
| `dashboard` | Dashboard | Home icon |
| `downloads` | Downloads | Download icon |
| `orders` | My purchases | Bag icon |
| `edit-account` | Settings | Gear icon |
| `customer-logout` | Logout | Door icon |

### 7.3 Settings Tabs

**Template:** `woocommerce/templates/myaccount/form-edit-account.php`

**Tabs:**
| Tab | ID | Fields |
|-----|-----|--------|
| Profile | `#bw-tab-profile` | First name, Last name, Display name |
| Billing Details | `#bw-tab-billing` | Country, Address, City, Postcode |
| Shipping Details | `#bw-tab-shipping` | Same as billing checkbox, Address fields |
| Security | `#bw-tab-security` | Password change, Email change |

### 7.4 Onboarding Flow

**Endpoint:** `/my-account/set-password/`

**Enforcement Logic:**
```php
function bw_mew_enforce_supabase_onboarding_lock() {
    if (!is_user_logged_in()) return;

    $user_id = get_current_user_id();
    if (get_user_meta($user_id, 'bw_supabase_onboarded', true) === '1') return;

    // Only allow set-password and logout endpoints
    $allowed = ['set-password', 'customer-logout'];
    $current = WC()->query->get_current_endpoint();

    if (!in_array($current, $allowed)) {
        wp_redirect(wc_get_account_endpoint_url('set-password'));
        exit;
    }
}
```

**Body Class:** `.bw-onboarding-lock` (dims navigation)

---

## 8. Configuration Options Reference

### 8.1 Account Page Options

| Option | Type | Default | Purpose |
|--------|------|---------|---------|
| `bw_account_login_image` | URL | - | Cover image URL |
| `bw_account_login_image_id` | int | 0 | Cover image ID |
| `bw_account_logo` | URL | - | Logo URL |
| `bw_account_logo_id` | int | 0 | Logo attachment ID |
| `bw_account_logo_width` | int | 180 | Logo width (px) |
| `bw_account_logo_padding_top` | int | 0 | Logo top padding |
| `bw_account_logo_padding_bottom` | int | 30 | Logo bottom padding |
| `bw_account_login_title` | string | - | Login heading |
| `bw_account_login_subtitle` | string | - | Login subheading |
| `bw_account_show_social_buttons` | bool | 0 | Show OAuth buttons |
| `bw_account_facebook` | bool | 0 | Enable Facebook |
| `bw_account_facebook_app_id` | string | - | Facebook App ID |
| `bw_account_facebook_app_secret` | string | - | Facebook App Secret |
| `bw_account_google` | bool | 0 | Enable Google |
| `bw_account_google_client_id` | string | - | Google Client ID |
| `bw_account_google_client_secret` | string | - | Google Client Secret |

### 8.2 Checkout Options

| Option | Type | Default | Purpose |
|--------|------|---------|---------|
| `bw_checkout_logo` | URL | - | Checkout logo |
| `bw_checkout_logo_align` | string | left | Logo alignment |
| `bw_checkout_logo_width` | int | 200 | Logo width |
| `bw_checkout_page_bg` | color | #ffffff | Page background |
| `bw_checkout_left_bg_color` | color | #ffffff | Left column bg |
| `bw_checkout_right_bg_color` | color | transparent | Right column bg |
| `bw_checkout_border_color` | color | #262626 | Border color |
| `bw_checkout_left_width` | int | 62 | Left column % |
| `bw_checkout_right_width` | int | 38 | Right column % |
| `bw_checkout_thumb_ratio` | string | square | Thumbnail aspect |
| `bw_checkout_thumb_width` | int | 110 | Thumbnail width |
| `bw_checkout_legal_text` | text | - | Legal disclaimer |
| `bw_checkout_footer_copyright_text` | text | - | Footer copyright |
| `bw_checkout_show_footer_copyright` | bool | 0 | Show copyright |
| `bw_checkout_show_return_to_shop` | bool | 0 | Show return link |

### 8.3 Complete Supabase Options

See [Section 3.3](#33-configuration-options-wp_options) for complete list.

---

## 9. AJAX Endpoints Reference

### 9.1 Authentication Endpoints

| Action | Handler | Auth Required | Purpose |
|--------|---------|---------------|---------|
| `bw_supabase_login` | `bw_mew_handle_supabase_login` | No | Password login |
| `bw_supabase_token_login` | `bw_mew_handle_supabase_token_login` | No | Bridge tokens |
| `bw_supabase_email_exists` | `bw_mew_handle_supabase_email_exists` | No | Check email |
| `bw_supabase_update_profile` | `bw_mew_handle_supabase_update_profile` | Yes | Update profile |
| `bw_supabase_update_password` | `bw_mew_handle_supabase_update_password` | Yes | Change password |
| `bw_supabase_update_email` | `bw_mew_handle_supabase_update_email` | Yes | Change email |
| `bw_supabase_check_wp_session` | `bw_mew_handle_supabase_session_check` | No | Check session |
| `bw_supabase_resend_invite` | `bw_mew_handle_supabase_resend_invite` | No | Resend invite |

### 9.2 Checkout Endpoints

| Action | Handler | Purpose |
|--------|---------|---------|
| `bw_apply_coupon` | `bw_mew_ajax_apply_coupon` | Apply coupon |
| `bw_remove_coupon` | `bw_mew_ajax_remove_coupon` | Remove coupon |
| `bw_brevo_test_connection` | Admin handler | Test Brevo API |

### 9.3 Nonce Keys

| Nonce | Action | Used In |
|-------|--------|---------|
| `bw-supabase-login` | Supabase auth | All auth AJAX |
| `bw-checkout-nonce` | Checkout | Coupon operations |
| `bw-google-pay-nonce` | Google Pay | Payment |
| `bw_checkout_subscribe_test` | Brevo test | Admin |
| `bw_account_page_nonce` | Settings save | Admin |

---

## 10. Implementation Guide for New Features

### 10.1 Adding a New Authentication Method

**Step 1: Add Configuration Options**
```php
// admin/class-blackwork-site-settings.php
// In Account Page > Technical Settings section

// Add to options array (around line 400)
'bw_supabase_new_method_enabled' => get_option('bw_supabase_new_method_enabled', '0'),

// Add form field (around line 1020)
<tr>
    <th scope="row">New Method</th>
    <td>
        <label>
            <input type="checkbox"
                   name="bw_supabase_new_method_enabled"
                   value="1"
                   <?php checked(1, $new_method_enabled); ?> />
            Enable new authentication method
        </label>
    </td>
</tr>

// Add save logic (around line 235)
update_option('bw_supabase_new_method_enabled',
    isset($_POST['bw_supabase_new_method_enabled']) ? '1' : '0');
```

**Step 2: Add Frontend UI**
```php
// woocommerce/templates/myaccount/form-login.php

<?php if ($new_method_enabled): ?>
<button data-bw-auth-method="new-method">
    Continue with New Method
</button>
<?php endif; ?>
```

**Step 3: Add JavaScript Handler**
```javascript
// assets/js/bw-account-page.js

// Add to initAuth() function
if (settings.newMethodEnabled) {
    initNewMethodAuth();
}

function initNewMethodAuth() {
    $('[data-bw-auth-method="new-method"]').addEventListener('click', async (e) => {
        e.preventDefault();
        // Implementation here
    });
}
```

**Step 4: Add Backend Handler (if needed)**
```php
// includes/woocommerce-overrides/class-bw-supabase-auth.php

add_action('wp_ajax_nopriv_bw_supabase_new_method', 'bw_mew_handle_new_method');
add_action('wp_ajax_bw_supabase_new_method', 'bw_mew_handle_new_method');

function bw_mew_handle_new_method() {
    check_ajax_referer('bw-supabase-login', 'nonce');
    // Implementation here
    wp_send_json_success(['message' => 'Success']);
}
```

### 10.2 Adding a New Checkout Field

**Step 1: Add to Fields Admin**
```php
// includes/admin/checkout-fields/class-bw-checkout-fields-admin.php

// Add to default fields array
$default_fields['billing']['billing_new_field'] = [
    'enabled' => true,
    'priority' => 25,
    'width' => 'full',
    'label' => 'New Field',
    'required' => false
];
```

**Step 2: Register with WooCommerce**
```php
// includes/admin/checkout-fields/class-bw-checkout-fields-frontend.php

add_filter('woocommerce_checkout_fields', function($fields) {
    $fields['billing']['billing_new_field'] = [
        'type' => 'text',
        'label' => __('New Field', 'bw'),
        'required' => false,
        'class' => ['form-row-wide'],
        'priority' => 25
    ];
    return $fields;
});
```

### 10.3 Adding a New Payment Gateway

**Step 1: Create Gateway Class**
```php
// includes/woocommerce-overrides/class-bw-new-gateway.php

class BW_New_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'bw_new_gateway';
        $this->has_fields = true;
        $this->method_title = 'New Gateway';
        $this->init_settings();
    }

    public function payment_fields() {
        echo '<div id="bw-new-gateway-container"></div>';
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        // Process payment
        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        ];
    }
}
```

**Step 2: Register Gateway**
```php
// woocommerce/woocommerce-init.php

add_filter('woocommerce_payment_gateways', function($gateways) {
    $gateways[] = 'BW_New_Gateway';
    return $gateways;
});
```

**Step 3: Add JavaScript**
```javascript
// assets/js/bw-new-gateway.js

jQuery(document).ready(function($) {
    const container = document.getElementById('bw-new-gateway-container');
    if (!container) return;

    // Initialize gateway UI
});
```

### 10.4 Adding a New Settings Tab

**Step 1: Add Tab Navigation**
```php
// admin/class-blackwork-site-settings.php (around line 170)

<a href="?page=blackwork-site-settings&tab=new-tab"
   class="nav-tab <?php echo $active_tab === 'new-tab' ? 'nav-tab-active' : ''; ?>">
    New Tab
</a>
```

**Step 2: Add Tab Content**
```php
// admin/class-blackwork-site-settings.php (around line 2500)

case 'new-tab':
    bw_site_render_new_tab();
    break;

// Define render function
function bw_site_render_new_tab() {
    ?>
    <form method="post" action="">
        <?php wp_nonce_field('bw_new_tab_save', 'bw_new_tab_nonce'); ?>
        <table class="form-table">
            <tr>
                <th>Setting</th>
                <td><input type="text" name="bw_new_setting" /></td>
            </tr>
        </table>
        <?php submit_button('Save Settings'); ?>
    </form>
    <?php
}
```

**Step 3: Handle Save**
```php
// At top of bw_site_settings_page() function

if (isset($_POST['bw_new_tab_submit'])) {
    check_admin_referer('bw_new_tab_save', 'bw_new_tab_nonce');
    update_option('bw_new_setting', sanitize_text_field($_POST['bw_new_setting']));
    echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
}
```

---

## 11. Data Flow Diagrams

### 11.1 Complete Authentication Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                        USER AUTHENTICATION                          │
└─────────────────────────────────────────────────────────────────────┘
                                   │
         ┌─────────────────────────┼─────────────────────────┐
         ↓                         ↓                         ↓
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Magic Link    │    │    Password     │    │     OAuth       │
│      (OTP)      │    │     Login       │    │ (Google/FB)     │
└────────┬────────┘    └────────┬────────┘    └────────┬────────┘
         │                      │                      │
         ↓                      ↓                      ↓
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│ POST /auth/v1/  │    │ POST /auth/v1/  │    │ signInWithOAuth │
│      otp        │    │     token       │    │   → Provider    │
└────────┬────────┘    └────────┬────────┘    └────────┬────────┘
         │                      │                      │
         ↓                      │                      ↓
┌─────────────────┐             │             ┌─────────────────┐
│  6-digit code   │             │             │ ?code=...       │
│    entered      │             │             │  callback       │
└────────┬────────┘             │             └────────┬────────┘
         │                      │                      │
         ↓                      │                      ↓
┌─────────────────┐             │             ┌─────────────────┐
│ POST /auth/v1/  │             │             │ PKCE exchange   │
│     verify      │             │             │   or SDK        │
└────────┬────────┘             │             └────────┬────────┘
         │                      │                      │
         └──────────────────────┼──────────────────────┘
                                ↓
                    ┌─────────────────────┐
                    │ access_token +      │
                    │ refresh_token       │
                    └──────────┬──────────┘
                               ↓
                    ┌─────────────────────┐
                    │ AJAX: bw_supabase_  │
                    │    token_login      │
                    └──────────┬──────────┘
                               ↓
                    ┌─────────────────────┐
                    │ bw_mew_supabase_    │
                    │   store_session()   │
                    └──────────┬──────────┘
                               ↓
              ┌────────────────┴────────────────┐
              ↓                                 ↓
    ┌─────────────────┐               ┌─────────────────┐
    │  Cookie Storage │               │ UserMeta Storage│
    │  _access        │               │ access_token    │
    │  _refresh       │               │ refresh_token   │
    └────────┬────────┘               └────────┬────────┘
              └────────────────┬───────────────┘
                               ↓
                    ┌─────────────────────┐
                    │ wp_set_auth_cookie  │
                    │    ($user_id)       │
                    └──────────┬──────────┘
                               ↓
                    ┌─────────────────────┐
                    │ Check: onboarded=1? │
                    └──────────┬──────────┘
                               │
              ┌────────────────┴────────────────┐
              ↓                                 ↓
    ┌─────────────────┐               ┌─────────────────┐
    │ YES: Redirect   │               │ NO: Redirect to │
    │ to /my-account/ │               │ /set-password/  │
    └─────────────────┘               └─────────────────┘
```

### 11.2 Checkout Order Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                       CHECKOUT ORDER FLOW                           │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────┐
│  User fills     │
│  checkout form  │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Newsletter      │
│ checkbox        │─────→ Store: _bw_subscribe_newsletter (order meta)
└────────┬────────┘
         ↓
┌─────────────────┐
│ Select payment  │
│ method          │
└────────┬────────┘
         ↓
┌─────────────────────────────────────────────────────────────────┐
│                     PAYMENT PROCESSING                          │
├─────────────────┬─────────────────┬─────────────────────────────┤
│     Stripe      │    PayPal       │      Google Pay             │
│  Card Element   │   Redirect      │   Payment Request API       │
└────────┬────────┴────────┬────────┴────────┬────────────────────┘
         └─────────────────┼─────────────────┘
                           ↓
                 ┌─────────────────┐
                 │ Order Created   │ → status: pending
                 └────────┬────────┘
                          ↓
                 ┌─────────────────┐
                 │ Payment Success │
                 └────────┬────────┘
                          ↓
                 ┌─────────────────┐
                 │ Order Status    │ → processing/completed
                 │    Change       │
                 └────────┬────────┘
                          │
         ┌────────────────┴────────────────┐
         ↓                                 ↓
┌─────────────────┐               ┌─────────────────┐
│ Newsletter?     │               │ Supabase        │
│ subscribe_timing│               │ Provisioning?   │
└────────┬────────┘               └────────┬────────┘
         │                                 │
         ↓                                 ↓
┌─────────────────┐               ┌─────────────────┐
│ Brevo API       │               │ Guest user?     │
│ - upsert contact│               │ Create WP user  │
│ - add to list   │               │ Send invite     │
└─────────────────┘               └─────────────────┘
```

---

## 12. Security Considerations

### 12.1 Authentication Security

**Rate Limiting:**
- Social login: 20 attempts per IP per hour (transient)
- Invite resend: 2-minute cooldown

**Token Security:**
- Cookies: `httponly`, `secure` (HTTPS), `SameSite=Lax`
- Access token: 1-hour expiry
- Refresh token: 30-day expiry
- Service role key: Server-side only (never in frontend)

**CSRF Protection:**
- WordPress nonce on all AJAX endpoints
- State token for OAuth flows (15-minute expiry)

### 12.2 Data Validation

**Input Sanitization:**
```php
esc_url_raw()           // URLs
sanitize_text_field()   // Single-line text
sanitize_textarea_field() // Multi-line text
sanitize_email()        // Email addresses
absint()                // Integers
wp_kses_post()          // HTML content
```

**Output Escaping:**
```php
esc_html()    // Text in HTML context
esc_attr()    // Attribute values
esc_url()     // URLs in HTML
wp_kses_post() // Safe HTML output
```

### 12.3 Sensitive Data Handling

**Never Logged:**
- Access tokens
- Refresh tokens
- Passwords
- API keys

**Hashed for Logging:**
- Email addresses (debug mode)

### 12.4 Best Practices Checklist

- [ ] Always use `check_ajax_referer()` for AJAX handlers
- [ ] Never expose service role key to frontend
- [ ] Sanitize all user inputs
- [ ] Escape all outputs
- [ ] Use prepared statements for database queries
- [ ] Validate redirect URLs before use
- [ ] Check user capabilities before admin operations
- [ ] Use transients with appropriate expiry for rate limiting

---

## Appendix A: File Reference Quick Lookup

| Feature | Primary File | Lines |
|---------|-------------|-------|
| Settings Page | `admin/class-blackwork-site-settings.php` | 6000+ |
| Supabase Auth | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | 1479 |
| Social Login | `includes/woocommerce-overrides/class-bw-social-login.php` | 548 |
| My Account | `includes/woocommerce-overrides/class-bw-my-account.php` | 421 |
| WC Init | `woocommerce/woocommerce-init.php` | 1558 |
| Login Form | `woocommerce/templates/myaccount/form-login.php` | 236 |
| Checkout | `woocommerce/templates/checkout/form-checkout.php` | 280 |
| Payment | `woocommerce/templates/checkout/payment.php` | 250 |
| Login JS | `assets/js/bw-account-page.js` | 1367 |
| Bridge JS | `assets/js/bw-supabase-bridge.js` | 387 |
| Checkout JS | `assets/js/bw-checkout.js` | 99KB |

---

## Appendix B: Troubleshooting

### Common Issues

**Technical Settings Tab Not Working:**
- Check browser console for JavaScript errors
- Verify jQuery is loaded
- Clear browser cache
- Check for corrupted strings in settings file

**Supabase Login Not Working:**
- Verify `bw_supabase_project_url` is set
- Verify `bw_supabase_anon_key` is set
- Enable debug mode and check error_log
- Verify redirect URLs are allowlisted in Supabase

**OAuth Callback Issues:**
- Check redirect URL is registered with provider
- Verify HTTPS is enabled
- Check PKCE code_verifier in localStorage
- Verify state token hasn't expired

**Checkout Coupon Issues:**
- Check session persistence
- Verify nonce is valid
- Check for WooCommerce AJAX conflicts

---

**Document Version:** 1.0
**Last Updated:** January 2026
**Maintained by:** BlackWork Development Team
