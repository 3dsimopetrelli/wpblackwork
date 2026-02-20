<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize WooCommerce customizations for BW Elementor Widgets.
 */
function bw_mew_initialize_woocommerce_overrides()
{
    if (!class_exists('WooCommerce')) {
        return;
    }

    $my_account_file = BW_MEW_PATH . 'includes/woocommerce-overrides/class-bw-my-account.php';
    $supabase_file = BW_MEW_PATH . 'includes/woocommerce-overrides/class-bw-supabase-auth.php';
    $email_styles_file = BW_MEW_PATH . 'includes/woocommerce-overrides/class-bw-email-styles.php';

    if (file_exists($my_account_file)) {
        require_once $my_account_file;
    }

    if (file_exists($supabase_file)) {
        require_once $supabase_file;
    }

    if (file_exists($email_styles_file)) {
        require_once $email_styles_file;
    }
    // Force "Ship to a different address?" checkbox to be unchecked by default
    add_filter('woocommerce_ship_to_different_address_checked', '__return_false');

    $notice_manager_file = BW_MEW_PATH . 'includes/woocommerce-overrides/class-bw-notice-manager.php';
    if (file_exists($notice_manager_file)) {
        require_once $notice_manager_file;
        BW_Notice_Manager::init();
    }

    $google_pay_file = BW_MEW_PATH . 'includes/woocommerce-overrides/class-bw-google-pay-gateway.php';
    if (file_exists($google_pay_file)) {
        require_once $google_pay_file;
    }

    add_filter('woocommerce_locate_template', 'bw_mew_locate_template', 1, 3);
    add_action('wp_enqueue_scripts', 'bw_mew_enqueue_related_products_assets', 30);
    add_action('wp_enqueue_scripts', 'bw_mew_enqueue_account_page_assets', 20);
    add_action('wp_enqueue_scripts', 'bw_mew_enqueue_checkout_assets', 20);
    add_action('wp_enqueue_scripts', 'bw_mew_enqueue_order_confirmation_assets', 20);
    add_filter('woocommerce_locate_core_template', 'bw_mew_locate_template', 1, 3);
    add_action('template_redirect', 'bw_mew_prepare_account_page_layout', 9);
    add_action('template_redirect', 'bw_mew_prepare_checkout_layout', 9);
    add_action('template_redirect', 'bw_mew_prepare_theme_title_bypass', 8);
    add_action('template_redirect', 'bw_mew_hide_single_product_notices', 9);
    add_action('woocommerce_checkout_update_order_review', 'bw_mew_sync_checkout_cart_quantities', 10, 1);
    add_filter('woocommerce_checkout_posted_data', 'bw_mew_sync_billing_from_shipping_mode', 20, 1);
    add_action('wp_ajax_bw_apply_coupon', 'bw_mew_ajax_apply_coupon');
    add_action('wp_ajax_nopriv_bw_apply_coupon', 'bw_mew_ajax_apply_coupon');
    add_action('wp_ajax_bw_remove_coupon', 'bw_mew_ajax_remove_coupon');
    add_action('wp_ajax_nopriv_bw_remove_coupon', 'bw_mew_ajax_remove_coupon');
    add_filter('the_title', 'bw_mew_filter_account_page_title', 10, 2);
    add_filter('woocommerce_available_payment_gateways', 'bw_mew_hide_paypal_advanced_card_processing');
    add_filter('wc_stripe_elements_options', 'bw_mew_customize_stripe_elements_style');
    add_filter('wc_stripe_elements_styling', 'bw_mew_customize_stripe_elements_style');
    add_filter('wc_stripe_upe_params', 'bw_mew_customize_stripe_upe_appearance');
    add_filter('body_class', 'bw_mew_add_section_heading_body_classes');
    add_action('woocommerce_checkout_before_customer_details', 'bw_mew_render_address_section_heading', 5);
    add_action('wp_enqueue_scripts', 'bw_mew_enqueue_cart_assets', 20);
    add_action('template_redirect', 'bw_mew_prepare_cart_layout', 9);
    add_filter('woocommerce_payment_gateways', 'bw_mew_add_google_pay_gateway');
}
add_action('plugins_loaded', 'bw_mew_initialize_woocommerce_overrides');

/**
 * Remove the theme-rendered page title on WooCommerce account pages.
 *
 * @param string $title   The post title.
 * @param int    $post_id Post ID.
 *
 * @return string
 */
function bw_mew_filter_account_page_title($title, $post_id)
{
    if (is_admin()) {
        return $title;
    }

    if (!function_exists('is_account_page') || !is_account_page()) {
        return $title;
    }

    if (!is_main_query() || !in_the_loop()) {
        return $title;
    }

    if (!is_singular()) {
        return $title;
    }

    return '';
}

/**
 * Force WooCommerce to use plugin templates before theme overrides.
 *
 * @param string $template      Located template path.
 * @param string $template_name Template name relative to template path.
 * @param string $template_path WooCommerce template path.
 *
 * @return string
 */
function bw_mew_locate_template($template, $template_name, $template_path)
{
    $plugin_template_path = trailingslashit(BW_MEW_PATH . 'woocommerce/templates');

    if ($template_name && file_exists($plugin_template_path . $template_name)) {
        return $plugin_template_path . $template_name;
    }

    return $template;
}

/**
 * Enqueue assets for WooCommerce related products layout.
 */
function bw_mew_enqueue_related_products_assets()
{
    if (!class_exists('WooCommerce') || !is_product()) {
        return;
    }

    if (function_exists('bw_register_wallpost_widget_assets')) {
        bw_register_wallpost_widget_assets();
    }

    if (wp_style_is('bw-wallpost-style', 'registered') && !wp_style_is('bw-wallpost-style', 'enqueued')) {
        wp_enqueue_style('bw-wallpost-style');
    }

    $css_file = BW_MEW_PATH . 'woocommerce/css/bw-related-products.css';
    $version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_enqueue_style(
        'bw-related-products-style',
        plugin_dir_url(__FILE__) . 'css/bw-related-products.css',
        ['bw-wallpost-style'],
        $version
    );
}

/**
 * Enqueue assets for the custom account/login layout.
 */
function bw_mew_enqueue_account_page_assets()
{
    if (!function_exists('is_account_page') || !is_account_page() || is_user_logged_in()) {
        return;
    }

    $css_file = BW_MEW_PATH . 'assets/css/bw-account-page.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';
    $js_file = BW_MEW_PATH . 'assets/js/bw-account-page.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_enqueue_script(
        'supabase-js',
        'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2',
        [],
        null,
        true
    );

    wp_enqueue_style(
        'bw-account-page',
        BW_MEW_URL . 'assets/css/bw-account-page.css',
        [],
        $css_version
    );

    wp_enqueue_script(
        'bw-account-page',
        BW_MEW_URL . 'assets/js/bw-account-page.js',
        ['supabase-js'],
        $js_version,
        true
    );

    wp_localize_script(
        'bw-account-page',
        'bwAccountAuth',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bw-supabase-login'),
            'supabaseWithOidc' => (int) get_option('bw_supabase_with_plugins', 0),
            'loginMode' => get_option('bw_supabase_login_mode', 'native'),
            'oidcAuthUrl' => function_exists('bw_oidc_get_auth_url') ? bw_oidc_get_auth_url() : '',
            'autoLoginAfterConfirm' => (int) get_option('bw_supabase_auto_login_after_confirm', 0),
            'projectUrl' => get_option('bw_supabase_project_url', ''),
            'anonKey' => get_option('bw_supabase_anon_key', ''),
            'magicLinkRedirectUrl' => get_option('bw_supabase_magic_link_redirect_url', site_url('/my-account/')),
            'oauthRedirectUrl' => get_option('bw_supabase_oauth_redirect_url', site_url('/my-account/')),
            'resetRedirectUrl' => site_url('/my-account/'),
            'magicLinkEnabled' => (int) get_option('bw_supabase_magic_link_enabled', 1),
            'oauthGoogleEnabled' => (int) get_option('bw_supabase_oauth_google_enabled', 1),
            'oauthFacebookEnabled' => (int) get_option('bw_supabase_oauth_facebook_enabled', 1),
            'oauthAppleEnabled' => (int) get_option('bw_supabase_oauth_apple_enabled', 0),
            'passwordLoginEnabled' => (int) get_option('bw_supabase_login_password_enabled', 1),
            'otpAllowSignup' => (int) get_option('bw_supabase_otp_allow_signup', 1),
            'debug' => (int) get_option('bw_supabase_debug_log', 0),
            'cookieBase' => sanitize_key((string) get_option('bw_supabase_jwt_cookie_name', 'bw_supabase_session')) ?: 'bw_supabase_session',
            'messages' => [
                'missingConfig' => esc_html__('Supabase configuration is missing.', 'bw'),
                'enterEmail' => esc_html__('Please enter your email address.', 'bw'),
                'magicLinkError' => esc_html__('Unable to send magic link.', 'bw'),
                'loginError' => esc_html__('Unable to login.', 'bw'),
                'otpSent' => esc_html__('If the email is valid, we sent you a code.', 'bw'),
                'enterOtp' => esc_html__('Please enter the 6-digit code.', 'bw'),
                'otpVerifyError' => esc_html__('Unable to verify the code.', 'bw'),
                'otpInvalid' => esc_html__('Invalid or expired code. Please try again.', 'bw'),
                'otpResent' => esc_html__('We sent you a new code.', 'bw'),
                'otpResendError' => esc_html__('Unable to resend the code right now.', 'bw'),
                'otpSignupDisabledNeutral' => esc_html__('We could not send a code. Please try a different email.', 'bw'),
                'otpRateLimit' => esc_html__('Too many attempts. Please wait and try again.', 'bw'),
                'otpRedirectInvalid' => esc_html__('Login is unavailable right now. Please contact support.', 'bw'),
                'createPasswordError' => esc_html__('Unable to update password.', 'bw'),
                'passwordMismatch' => esc_html__('Passwords do not match.', 'bw'),
                'passwordRules' => esc_html__('Please meet all password requirements.', 'bw'),
                'oauthAppleSoon' => esc_html__('Apple login is coming soon.', 'bw'),
                'supabaseSdkMissing' => esc_html__('Supabase JS SDK is not loaded.', 'bw'),
            ],
        ]
    );
}

/**
 * Enqueue Supabase invite token bridge on the frontend.
 */
function bw_mew_enqueue_supabase_bridge()
{
    if (is_user_logged_in()) {
        return;
    }

    $js_file = BW_MEW_PATH . 'assets/js/bw-supabase-bridge.js';
    if (!file_exists($js_file)) {
        return;
    }

    // Ensure the dependency exists on every landing page (home included),
    // otherwise the bridge script is skipped by WP when invites redirect there.
    if (!wp_script_is('supabase-js', 'registered')) {
        wp_register_script(
            'supabase-js',
            'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2',
            [],
            null,
            true
        );
    }
    if (!wp_script_is('supabase-js', 'enqueued')) {
        wp_enqueue_script('supabase-js');
    }

    wp_enqueue_script(
        'bw-supabase-bridge',
        BW_MEW_URL . 'assets/js/bw-supabase-bridge.js',
        ['supabase-js'],
        filemtime($js_file),
        true
    );

    $expired_link_url = trim((string) get_option('bw_supabase_expired_link_redirect_url', ''));
    if (!$expired_link_url) {
        $expired_link_url = site_url('/link-expired/');
    }

    $account_url  = wc_get_page_permalink( 'myaccount' );
    $callback_url = $account_url ? add_query_arg( 'bw_auth_callback', '1', $account_url ) : site_url( '/my-account/?bw_auth_callback=1' );

    wp_localize_script(
        'bw-supabase-bridge',
        'bwSupabaseBridge',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bw-supabase-login'),
            'accountUrl' => $account_url,
            'callbackUrl' => $callback_url,
            'setPasswordUrl' => wc_get_account_endpoint_url('set-password'),
            'expiredLinkUrl' => $expired_link_url,
            'projectUrl' => get_option('bw_supabase_project_url', ''),
            'anonKey' => get_option('bw_supabase_anon_key', ''),
            'debug' => (int) get_option('bw_supabase_debug_log', 0),
        ]
    );
}
add_action('wp_enqueue_scripts', 'bw_mew_enqueue_supabase_bridge', 20);

/**
 * Early invite redirect to avoid home-page flash before My Account bridge.
 *
 * Supabase invite links may land on non-account pages with hash tokens.
 * We move the browser to /my-account/ immediately (keeping hash) so the
 * bridge and password modal flow starts without visible intermediate page.
 */
function bw_mew_supabase_early_invite_redirect_hint()
{
    $account_url = wc_get_page_permalink('myaccount');
    if (!$account_url) {
        return;
    }
    $callback_url = add_query_arg( 'bw_auth_callback', '1', $account_url );
    $set_password_url = wc_get_account_endpoint_url('set-password');
    if (!$set_password_url) {
        $set_password_url = $account_url;
    }
    $expired_link_url = trim((string) get_option('bw_supabase_expired_link_redirect_url', ''));
    if (!$expired_link_url) {
        $expired_link_url = site_url('/link-expired/');
    }
    $is_logged_in = is_user_logged_in();

    ?>
    <style id="bw-supabase-auth-preload">
        html.bw-auth-preload body.woocommerce-account .bw-account-login__content {
            visibility: hidden !important;
        }

        html.bw-auth-preload body.woocommerce-account .bw-auth-callback {
            display: flex !important;
            min-height: 60vh;
            align-items: center;
            justify-content: center;
        }
    </style>
    <script>
    (function () {
        var isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
        var normalizePath = function (path) {
            return (path || '').replace(/\/+$/, '') || '/';
        };
        var accountPath = normalizePath(new URL(<?php echo wp_json_encode($account_url); ?>, window.location.origin).pathname);
        var currentUrl = new URL(window.location.href);
        var currentPath = normalizePath(currentUrl.pathname);
        var onAccountPage = currentPath === accountPath;
        var search = currentUrl.searchParams;
        var callbackMode = search.get('bw_auth_callback') === '1';
        var setPasswordMode = search.get('bw_set_password') === '1';
        var code = search.get('code') || '';
        var typeFromQuery = search.get('type') || '';
        var hash = window.location.hash || '';
        var hashParams = hash ? new URLSearchParams(hash.replace(/^#/, '')) : null;
        var hashType = hashParams ? (hashParams.get('type') || '') : '';
        var hasInviteHash = !!hash && hash.indexOf('access_token=') !== -1 && (hashType === 'invite' || hashType === 'recovery');
        var hasInviteCode = !!code && (typeFromQuery === 'invite' || typeFromQuery === 'recovery');

        try {
            if (window.sessionStorage) {
                if (isLoggedIn) {
                    sessionStorage.removeItem('bw_auth_in_progress');
                }
                if (hasInviteHash || hasInviteCode || callbackMode || setPasswordMode) {
                    sessionStorage.setItem('bw_auth_in_progress', '1');
                }
                if (search.get('logged_out') === '1') {
                    sessionStorage.removeItem('bw_auth_in_progress');
                }
            }
        } catch (e) {}

        var authInProgress = false;
        try {
            authInProgress = !!(window.sessionStorage && sessionStorage.getItem('bw_auth_in_progress') === '1');
        } catch (e) {}

        // Stale callback URL (common after logout): no auth payload available.
        // Avoid showing the callback loader forever and go back to clean My Account.
        if (!isLoggedIn && onAccountPage && callbackMode && !setPasswordMode && !hasInviteHash && !hasInviteCode) {
            try {
                if (window.sessionStorage) {
                    sessionStorage.removeItem('bw_auth_in_progress');
                }
            } catch (e) {}
            window.location.replace(<?php echo wp_json_encode($account_url); ?>);
            return;
        }

        if (!isLoggedIn && onAccountPage && (authInProgress || hasInviteHash || hasInviteCode || callbackMode || setPasswordMode)) {
            document.documentElement.classList.add('bw-auth-preload');
        }

        if (!isLoggedIn && onAccountPage && authInProgress && !callbackMode && !setPasswordMode && !hasInviteHash && !hasInviteCode) {
            window.location.replace(<?php echo wp_json_encode($callback_url); ?>);
            return;
        }

        if (code && (typeFromQuery === 'invite' || typeFromQuery === 'recovery')) {
            if (currentUrl.searchParams.get('bw_auth_callback') !== '1') {
                var codeTarget = new URL(<?php echo wp_json_encode($callback_url); ?>, window.location.origin);
                codeTarget.searchParams.set('code', code);
                codeTarget.searchParams.set('type', typeFromQuery);

                var state = currentUrl.searchParams.get('state') || '';
                var provider = currentUrl.searchParams.get('provider') || '';
                if (state) {
                    codeTarget.searchParams.set('state', state);
                }
                if (provider) {
                    codeTarget.searchParams.set('provider', provider);
                }

                window.location.replace(codeTarget.toString());
            }
            return;
        }

        if (!hash) {
            return;
        }

        var params = new URLSearchParams(hash.replace(/^#/, ''));
        var errorCode = params.get('error_code') || '';
        if (errorCode === 'otp_expired') {
            var targetBase = isLoggedIn
                ? <?php echo wp_json_encode($set_password_url); ?>
                : <?php echo wp_json_encode($expired_link_url); ?>;
            var targetUrl = new URL(targetBase, window.location.origin);
            if (isLoggedIn) {
                targetUrl.searchParams.set('bw_set_password', '1');
            }
            window.location.replace(targetUrl.toString());
            return;
        }

        if (hash.indexOf('access_token=') === -1) {
            return;
        }

        var type = params.get('type') || '';
        if (type !== 'invite' && type !== 'recovery') {
            return;
        }

        var target = new URL(<?php echo wp_json_encode($callback_url); ?>, window.location.origin);
        var current = new URL(window.location.href);
        var targetPath = target.pathname.replace(/\/+$/, '');
        var currentPath = current.pathname.replace(/\/+$/, '');

        if (targetPath === currentPath && current.search.indexOf('bw_auth_callback=1') !== -1) {
            return;
        }

        target.hash = hash.replace(/^#/, '');
        window.location.replace(target.toString());
    })();
    </script>
    <?php
}
add_action('wp_head', 'bw_mew_supabase_early_invite_redirect_hint', 1);

/**
 * Enqueue assets for the custom checkout layout and expose colors as CSS variables.
 */
function bw_mew_enqueue_checkout_assets()
{
    if (!bw_mew_is_checkout_request()) {
        return;
    }

    $css_file = BW_MEW_PATH . 'assets/css/bw-checkout.css';
    $js_file = BW_MEW_PATH . 'assets/js/bw-checkout.js';
    $version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';
    $settings = bw_mew_get_checkout_settings();

    wp_enqueue_style(
        'bw-checkout',
        BW_MEW_URL . 'assets/css/bw-checkout.css',
        [],
        $version
    );

    if (file_exists($js_file)) {
        $js_version = filemtime($js_file);
        $dependencies = ['jquery'];

        if (wp_script_is('wc-checkout', 'registered')) {
            wp_enqueue_script('wc-checkout');
            $dependencies[] = 'wc-checkout';
        }

        wp_enqueue_script(
            'bw-checkout',
            BW_MEW_URL . 'assets/js/bw-checkout.js',
            $dependencies,
            $js_version,
            true
        );

        // Get free order message and button text from settings
        $free_order_message = get_option('bw_checkout_free_order_message', '');
        $free_order_button_text = get_option('bw_checkout_free_order_button_text', '');

        if (empty($free_order_message)) {
            $free_order_message = __('Your order is free. Complete your details and click Place order.', 'bw');
        }
        if (empty($free_order_button_text)) {
            $free_order_button_text = __('Confirm free order', 'bw');
        }

        wp_localize_script(
            'bw-checkout',
            'bwCheckoutParams',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bw-checkout-nonce'),
                'freeOrderMessage' => wp_kses_post(wpautop($free_order_message)),
                'freeOrderButtonText' => esc_html($free_order_button_text),
            )
        );

        // Google Maps settings for floating labels + autocomplete
        $google_maps_enabled = get_option('bw_google_maps_enabled', '0');
        $google_maps_api_key = get_option('bw_google_maps_api_key', '');
        $google_maps_autofill = get_option('bw_google_maps_autofill', '1');
        $google_maps_restrict = get_option('bw_google_maps_restrict_country', '1');

        // Load Google Maps API if enabled and API key exists
        if ('1' === $google_maps_enabled && !empty($google_maps_api_key)) {
            wp_enqueue_script(
                'google-maps-places',
                'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($google_maps_api_key) . '&libraries=places',
                [],
                null,
                true
            );
        }

        // Pass Google Maps settings to JavaScript
        wp_localize_script(
            'bw-checkout',
            'bwGoogleMapsSettings',
            array(
                'enabled' => '1' === $google_maps_enabled && !empty($google_maps_api_key),
                'autoFillCityPostcode' => '1' === $google_maps_autofill,
                'restrictToCountry' => '1' === $google_maps_restrict,
            )
        );
    }

    $inline_styles = '.bw-checkout-form{--bw-checkout-left-bg:' . esc_attr($settings['left_bg']) . ';--bw-checkout-right-bg:' . esc_attr($settings['right_bg']) . ';--bw-checkout-border-color:' . esc_attr($settings['border_color']) . ';}';

    wp_add_inline_style('bw-checkout', $inline_styles);

    // Enqueue payment methods assets (Shopify-style accordion)
    $payment_css_file = BW_MEW_PATH . 'assets/css/bw-payment-methods.css';
    $payment_js_file = BW_MEW_PATH . 'assets/js/bw-payment-methods.js';

    if (file_exists($payment_css_file)) {
        wp_enqueue_style(
            'bw-payment-methods',
            BW_MEW_URL . 'assets/css/bw-payment-methods.css',
            ['bw-checkout'],
            filemtime($payment_css_file)
        );
    }

    if (file_exists($payment_js_file)) {
        wp_enqueue_script(
            'bw-payment-methods',
            BW_MEW_URL . 'assets/js/bw-payment-methods.js',
            ['jquery', 'wc-checkout'],
            filemtime($payment_js_file),
            true
        );
    }

    // Enqueue checkout notices assets (moves notices into left column with custom styling)
    $notices_css_file = BW_MEW_PATH . 'assets/css/bw-checkout-notices.css';
    $notices_js_file = BW_MEW_PATH . 'assets/js/bw-checkout-notices.js';

    if (file_exists($notices_css_file)) {
        wp_enqueue_style(
            'bw-checkout-notices',
            BW_MEW_URL . 'assets/css/bw-checkout-notices.css',
            ['bw-checkout'],
            filemtime($notices_css_file)
        );
    }

    if (file_exists($notices_js_file)) {
        wp_enqueue_script(
            'bw-checkout-notices',
            BW_MEW_URL . 'assets/js/bw-checkout-notices.js',
            ['jquery', 'wc-checkout'],
            filemtime($notices_js_file),
            true
        );
    }

    // Enqueue Stripe UPE cleaner to hide "Card" accordion header
    $stripe_upe_cleaner_file = BW_MEW_PATH . 'assets/js/bw-stripe-upe-cleaner.js';
    if (file_exists($stripe_upe_cleaner_file)) {
        wp_enqueue_script(
            'bw-stripe-upe-cleaner',
            BW_MEW_URL . 'assets/js/bw-stripe-upe-cleaner.js',
            ['jquery', 'wc-checkout'],
            filemtime($stripe_upe_cleaner_file),
            true
        );
    }

    // Google Pay Integration
    if (get_option('bw_google_pay_enabled', '0') === '1') {
        $google_pay_js = BW_MEW_PATH . 'assets/js/bw-google-pay.js';
        if (file_exists($google_pay_js)) {
            wp_enqueue_script('stripe', 'https://js.stripe.com/v3/', [], null, true);
            wp_enqueue_script(
                'bw-google-pay',
                BW_MEW_URL . 'assets/js/bw-google-pay.js',
                ['jquery', 'stripe', 'wc-checkout'],
                filemtime($google_pay_js),
                true
            );

            $test_mode = get_option('bw_google_pay_test_mode', '0') === '1';
            $pub_key = $test_mode
                ? get_option('bw_google_pay_test_publishable_key', '')
                : get_option('bw_google_pay_publishable_key', '');

            wp_localize_script('bw-google-pay', 'bwGooglePayParams', [
                'publishableKey'   => $pub_key,
                'testMode'         => $test_mode,
                'country'          => WC()->countries->get_base_country(),
                'currency'         => strtolower( get_woocommerce_currency() ),
                'ajaxCheckoutUrl'  => add_query_arg( 'wc-ajax', 'checkout', home_url( '/' ) ),
            ]);
        }
    }
}

/**
 * Enqueue assets for the order confirmation (thank you) page.
 */
function bw_mew_enqueue_order_confirmation_assets()
{
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-received')) {
        return;
    }

    $css_file = BW_MEW_PATH . 'assets/css/bw-order-confirmation.css';
    $version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_enqueue_style(
        'bw-order-confirmation',
        BW_MEW_URL . 'assets/css/bw-order-confirmation.css',
        [],
        $version
    );
}

/**
 * Prevent full-page cache from serving stale order-received pages.
 */
function bw_mew_prevent_order_received_cache()
{
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-received')) {
        return;
    }

    if (function_exists('nocache_headers')) {
        nocache_headers();
    }

    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
}
add_action('template_redirect', 'bw_mew_prevent_order_received_cache', 1);

/**
 * Redirect Klarna failed/canceled returns away from order-received to checkout.
 */
function bw_mew_handle_klarna_failed_return_redirect()
{
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-received')) {
        return;
    }

    $redirect_status = isset($_GET['redirect_status']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ? sanitize_text_field(wp_unslash($_GET['redirect_status'])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        : '';
    if ('failed' !== $redirect_status && 'canceled' !== $redirect_status) {
        return;
    }

    $order_id = absint(get_query_var('order-received'));
    if ($order_id <= 0 && isset($_GET['order-received'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order_id = absint(wp_unslash($_GET['order-received'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }
    if ($order_id <= 0) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order || 'bw_klarna' !== $order->get_payment_method()) {
        return;
    }

    wc_add_notice(
        __('Klarna payment was canceled or failed. Please choose another payment method or try again.', 'bw'),
        'error'
    );

    $checkout_url = function_exists('wc_get_checkout_url')
        ? wc_get_checkout_url()
        : wc_get_page_permalink('checkout');

    wp_safe_redirect($checkout_url);
    exit;
}
add_action('template_redirect', 'bw_mew_handle_klarna_failed_return_redirect', 2);

/**
 * Add a specific body class and hide theme wrappers on the custom login page.
 */
function bw_mew_prepare_account_page_layout()
{
    if (!function_exists('is_account_page') || !is_account_page() || is_user_logged_in()) {
        return;
    }

    add_filter('woocommerce_show_page_title', '__return_false');
    add_filter('body_class', static function ($classes) {
        $classes[] = 'bw-account-login-only';
        return $classes;
    });

    // Ensure the hiding styles are available even if the theme prints header/footer.
    add_action('wp_enqueue_scripts', static function () {
        if (wp_style_is('bw-account-page', 'enqueued')) {
            $css = 'body.bw-account-login-only header, body.bw-account-login-only .site-header, body.bw-account-login-only footer, body.bw-account-login-only .site-footer, body.bw-account-login-only .woocommerce-breadcrumb, body.bw-account-login-only .page-title, body.bw-account-login-only .entry-title { display: none !important; } body.bw-account-login-only .entry-content { padding: 0; margin: 0; } body.bw-account-login-only .site-content, body.bw-account-login-only .content-area, body.bw-account-login-only .site-main { padding: 0; margin: 0; }';
            wp_add_inline_style('bw-account-page', $css);
        }
    }, 25);
}

/**
 * Hide the default page title on logged-in account pages.
 */
function bw_mew_hide_logged_in_account_title()
{
    if (!function_exists('is_account_page') || !is_account_page() || !is_user_logged_in()) {
        return;
    }

    add_filter('woocommerce_show_page_title', '__return_false');
}
add_action('template_redirect', 'bw_mew_hide_logged_in_account_title', 9);

/**
 * Determine whether the theme title bypass is enabled for a context.
 *
 * @param string $context Context key (account|checkout).
 *
 * @return bool
 */
function bw_mew_is_theme_title_bypass_enabled($context)
{
    $enabled = false;

    if ('account' === $context) {
        $enabled = function_exists('is_account_page') && is_account_page() && is_user_logged_in();
    } elseif ('checkout' === $context) {
        $enabled = bw_mew_is_checkout_request()
            && (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-received'));
    }

    return (bool) apply_filters('bw_mew_enable_theme_title_bypass', $enabled, $context);
}

/**
 * Prepare theme title bypass helpers on account/checkout pages.
 */
function bw_mew_prepare_theme_title_bypass()
{
    if (!bw_mew_is_theme_title_bypass_enabled('account') && !bw_mew_is_theme_title_bypass_enabled('checkout')) {
        return;
    }

    add_filter('body_class', 'bw_mew_add_theme_title_bypass_class');
    add_action('wp_enqueue_scripts', 'bw_mew_dequeue_theme_title_styles', 100);
    add_action('wp_enqueue_scripts', 'bw_mew_enqueue_theme_title_bypass_css', 100);
}

/**
 * Add a body class for the theme title bypass scope.
 *
 * @param array $classes Body classes.
 *
 * @return array
 */
function bw_mew_add_theme_title_bypass_class($classes)
{
    if (bw_mew_is_theme_title_bypass_enabled('account') || bw_mew_is_theme_title_bypass_enabled('checkout')) {
        $classes[] = 'bw-theme-title-bypass';
    }

    return $classes;
}

/**
 * Dequeue parent theme title styles on targeted pages.
 */
function bw_mew_dequeue_theme_title_styles()
{
    if (!bw_mew_is_theme_title_bypass_enabled('account') && !bw_mew_is_theme_title_bypass_enabled('checkout')) {
        return;
    }

    $handles = (array) apply_filters('bw_mew_theme_title_bypass_handles', [], [
        'account' => bw_mew_is_theme_title_bypass_enabled('account'),
        'checkout' => bw_mew_is_theme_title_bypass_enabled('checkout'),
    ]);

    foreach ($handles as $handle) {
        if (!is_string($handle) || '' === $handle) {
            continue;
        }

        wp_dequeue_style($handle);
        wp_deregister_style($handle);
    }
}

/**
 * Add scoped reset styles to neutralize theme title CSS on targeted pages.
 */
function bw_mew_enqueue_theme_title_bypass_css()
{
    if (!bw_mew_is_theme_title_bypass_enabled('account') && !bw_mew_is_theme_title_bypass_enabled('checkout')) {
        return;
    }

    $css = '.bw-theme-title-bypass .page-header .entry-title,'
        . '.bw-theme-title-bypass .entry-header .entry-title,'
        . '.bw-theme-title-bypass .page-title{'
        . 'font-size:inherit;line-height:inherit;letter-spacing:normal;'
        . 'text-align:inherit;font-weight:inherit;}';

    if (bw_mew_is_theme_title_bypass_enabled('account') && wp_style_is('bw-my-account', 'enqueued')) {
        wp_add_inline_style('bw-my-account', $css);
    }

    if (bw_mew_is_theme_title_bypass_enabled('checkout') && wp_style_is('bw-checkout', 'enqueued')) {
        wp_add_inline_style('bw-checkout', $css);
    }
}

/**
 * Hide checkout notices and prepare layout.
 */
function bw_mew_prepare_checkout_layout()
{
    // Remove all WooCommerce notices from checkout page
    // remove_action('woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10);

    // Remove "Have a coupon?" banner that appears above checkout form
    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);

    // Avoid rendering the payment section (and its button) twice by keeping it only in the left column.
    // Avoid rendering the payment section (and its button) twice by keeping it only in the left column.
    remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
}

/**
 * Check if the current request should be treated as checkout.
 *
 * @return bool
 */
function bw_mew_is_checkout_request()
{
    if (function_exists('is_checkout') && is_checkout() && !is_cart()) {
        return true;
    }

    if (function_exists('is_page')) {
        $checkout_page_id = function_exists('wc_get_page_id') ? wc_get_page_id('checkout') : 0;
        if ($checkout_page_id && is_page($checkout_page_id)) {
            return true;
        }
    }

    if (!empty($_POST['apply_coupon']) || !empty($_POST['woocommerce-apply-coupon-nonce'])) {
        return true;
    }

    return false;
}

if (!function_exists('bw_mew_normalize_checkout_column_widths')) {
    /**
     * Normalize checkout column widths ensuring sane bounds and total of 100%.
     *
     * @param int $left  Desired left column percentage.
     * @param int $right Desired right column percentage.
     *
     * @return array{left:int,right:int}
     */
    function bw_mew_normalize_checkout_column_widths($left, $right)
    {
        $left = min(90, max(10, absint($left)));
        $right = min(90, max(10, absint($right)));

        if (($left + $right) > 100) {
            $total = $left + $right;
            $left = (int) round(($left / $total) * 100);
            $right = 100 - $left;
        }

        return [
            'left' => $left,
            'right' => $right,
        ];
    }
}

/**
 * Hide the standard WooCommerce notice wrapper on single product pages only.
 */
function bw_mew_hide_single_product_notices()
{
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    remove_action('woocommerce_before_single_product', 'woocommerce_output_all_notices', 10);
    remove_action('woocommerce_before_single_product_summary', 'woocommerce_output_all_notices', 10);
}

/**
 * Handle social login start and callback.
 *
 * @deprecated Use BW_Social_Login class instead.
 */
function bw_mew_handle_social_login_requests()
{
    if (!function_exists('is_account_page') || !is_account_page()) {
        return;
    }

    if (isset($_GET['bw_social_login'])) {
        bw_mew_social_login_redirect(sanitize_key(wp_unslash($_GET['bw_social_login'])));
    }

    if (isset($_GET['bw_social_login_callback'])) {
        bw_mew_process_social_login_callback(sanitize_key(wp_unslash($_GET['bw_social_login_callback'])));
    }
}

/**
 * Sync cart quantities during checkout AJAX refreshes so totals update immediately.
 *
 * @param string $posted_data Serialized form data.
 */
function bw_mew_sync_checkout_cart_quantities($posted_data)
{
    if (empty($posted_data) || !WC()->cart) {
        return;
    }

    parse_str($posted_data, $parsed);

    if (empty($parsed['cart']) || !is_array($parsed['cart'])) {
        return;
    }

    foreach ($parsed['cart'] as $cart_item_key => $values) {
        if (!isset($values['qty'])) {
            continue;
        }

        $qty = max(0, wc_stock_amount(wp_unslash($values['qty'])));
        WC()->cart->set_quantity($cart_item_key, $qty, false);
    }

    WC()->cart->calculate_totals();
}

/**
 * Sync shipping address from billing fields when checkout mode is "same as billing".
 *
 * @param array $data Posted checkout data.
 * @return array
 */
function bw_mew_sync_billing_from_shipping_mode($data)
{
    if (!is_array($data)) {
        return $data;
    }

    $needs_shipping_address = WC()->cart && WC()->cart->needs_shipping_address();
    $mode = isset($data['bw_shipping_address_mode']) ? sanitize_key((string) $data['bw_shipping_address_mode']) : 'same';
    $data['bw_shipping_address_mode'] = $mode;

    if (!$needs_shipping_address) {
        return $data;
    }

    // Shipping fields are always shown when a shipping address is required.
    $data['ship_to_different_address'] = 1;

    if ('different' === $mode) {
        return $data;
    }

    $map = [
        'billing_first_name' => 'shipping_first_name',
        'billing_last_name' => 'shipping_last_name',
        'billing_company' => 'shipping_company',
        'billing_country' => 'shipping_country',
        'billing_address_1' => 'shipping_address_1',
        'billing_address_2' => 'shipping_address_2',
        'billing_city' => 'shipping_city',
        'billing_postcode' => 'shipping_postcode',
        'billing_state' => 'shipping_state',
    ];

    foreach ($map as $billing_key => $shipping_key) {
        if (!array_key_exists($billing_key, $data)) {
            continue;
        }

        $billing_value = is_string($data[$billing_key]) ? trim($data[$billing_key]) : $data[$billing_key];
        $data[$shipping_key] = $billing_value;
    }

    return $data;
}

/**
 * Build social login start URL.
 *
 * @param string $provider Social provider key.
 * @return string
 */
function bw_mew_get_social_login_url($provider)
{
    if (!class_exists('BW_Social_Login')) {
        return '';
    }

    return BW_Social_Login::get_login_url($provider);
}

/**
 * Get the callback URL for the provider.
 *
 * @param string $provider Provider key.
 * @return string
 */
function bw_mew_get_social_redirect_uri($provider)
{
    if (!class_exists('BW_Social_Login')) {
        return '';
    }

    return BW_Social_Login::get_redirect_uri($provider);
}

/**
 * Retrieve checkout style and content options.
 *
 * @return array{logo:string,logo_align:string,page_bg:string,grid_bg:string,left_bg:string,right_bg:string,border_color:string,legal_text:string,left_width:int,right_width:int,thumb_ratio:string,thumb_width:int,right_sticky_top:int,right_padding_top:int,right_padding_right:int,right_padding_bottom:int,right_padding_left:int,footer_copyright:string,show_return_to_shop:string}
 */
function bw_mew_get_checkout_settings()
{
    $defaults = [
        'logo' => '',
        'logo_align' => 'left',
        'page_bg' => '#ffffff',
        'grid_bg' => '#ffffff',
        'logo_width' => 200,
        'logo_padding_top' => 0,
        'logo_padding_right' => 0,
        'logo_padding_bottom' => 30,
        'logo_padding_left' => 0,
        'show_order_heading' => '1',
        'left_bg' => '#ffffff',
        'right_bg' => 'transparent',
        'border_color' => '#262626',
        'legal_text' => '',
        'left_width' => 62,
        'right_width' => 38,
        'thumb_ratio' => 'square',
        'thumb_width' => 110,
        'right_sticky_top' => 20,
        'right_margin_top' => 0,
        'right_padding_top' => 0,
        'right_padding_right' => 0,
        'right_padding_bottom' => 0,
        'right_padding_left' => 28,
        'footer_copyright' => '',
        'show_footer_copyright' => '1',
        'show_return_to_shop' => '1',
    ];

    $settings = [
        'logo' => esc_url_raw(get_option('bw_checkout_logo', $defaults['logo'])),
        'logo_align' => sanitize_key(get_option('bw_checkout_logo_align', $defaults['logo_align'])),
        'logo_width' => absint(get_option('bw_checkout_logo_width', $defaults['logo_width'])),
        'page_bg' => sanitize_hex_color(get_option('bw_checkout_page_bg', get_option('bw_checkout_page_bg_color', $defaults['page_bg']))),
        'grid_bg' => sanitize_hex_color(get_option('bw_checkout_grid_bg', get_option('bw_checkout_grid_bg_color', $defaults['grid_bg']))),
        'logo_padding_top' => absint(get_option('bw_checkout_logo_padding_top', $defaults['logo_padding_top'])),
        'logo_padding_right' => absint(get_option('bw_checkout_logo_padding_right', $defaults['logo_padding_right'])),
        'logo_padding_bottom' => absint(get_option('bw_checkout_logo_padding_bottom', $defaults['logo_padding_bottom'])),
        'logo_padding_left' => absint(get_option('bw_checkout_logo_padding_left', $defaults['logo_padding_left'])),
        'show_order_heading' => get_option('bw_checkout_show_order_heading', $defaults['show_order_heading']),
        'left_bg' => sanitize_hex_color(get_option('bw_checkout_left_bg_color', $defaults['left_bg'])),
        'right_bg' => sanitize_hex_color(get_option('bw_checkout_right_bg_color', $defaults['right_bg'])),
        'border_color' => sanitize_hex_color(get_option('bw_checkout_border_color', $defaults['border_color'])),
        'legal_text' => get_option('bw_checkout_legal_text', $defaults['legal_text']),
        'left_width' => absint(get_option('bw_checkout_left_width', $defaults['left_width'])),
        'right_width' => absint(get_option('bw_checkout_right_width', $defaults['right_width'])),
        'thumb_ratio' => sanitize_key(get_option('bw_checkout_thumb_ratio', $defaults['thumb_ratio'])),
        'thumb_width' => absint(get_option('bw_checkout_thumb_width', $defaults['thumb_width'])),
        'right_sticky_top' => absint(get_option('bw_checkout_right_sticky_top', $defaults['right_sticky_top'])),
        'right_margin_top' => absint(get_option('bw_checkout_right_margin_top', $defaults['right_margin_top'])),
        'right_padding_top' => absint(get_option('bw_checkout_right_padding_top', $defaults['right_padding_top'])),
        'right_padding_right' => absint(get_option('bw_checkout_right_padding_right', $defaults['right_padding_right'])),
        'right_padding_bottom' => absint(get_option('bw_checkout_right_padding_bottom', $defaults['right_padding_bottom'])),
        'right_padding_left' => absint(get_option('bw_checkout_right_padding_left', $defaults['right_padding_left'])),
        'footer_copyright' => get_option('bw_checkout_footer_copyright_text', $defaults['footer_copyright']),
        'show_footer_copyright' => get_option('bw_checkout_show_footer_copyright', $defaults['show_footer_copyright']),
        'show_return_to_shop' => get_option('bw_checkout_show_return_to_shop', $defaults['show_return_to_shop']),
    ];

    $settings['logo_align'] = in_array($settings['logo_align'], ['left', 'center', 'right'], true) ? $settings['logo_align'] : $defaults['logo_align'];
    $settings['page_bg'] = $settings['page_bg'] ?: $defaults['page_bg'];
    $settings['grid_bg'] = $settings['grid_bg'] ?: $defaults['grid_bg'];
    $settings['left_bg'] = $settings['left_bg'] ?: $defaults['left_bg'];
    $settings['right_bg'] = $settings['right_bg'] ?: $defaults['right_bg'];
    $settings['border_color'] = $settings['border_color'] ?: $defaults['border_color'];
    $settings['thumb_ratio'] = in_array($settings['thumb_ratio'], ['square', 'portrait', 'landscape'], true) ? $settings['thumb_ratio'] : $defaults['thumb_ratio'];

    // Validate thumb_width bounds
    if ($settings['thumb_width'] < 50) {
        $settings['thumb_width'] = 50;
    }
    if ($settings['thumb_width'] > 300) {
        $settings['thumb_width'] = 300;
    }

    if (function_exists('bw_mew_normalize_checkout_column_widths')) {
        $normalized = bw_mew_normalize_checkout_column_widths($settings['left_width'], $settings['right_width']);
        $settings['left_width'] = $normalized['left'];
        $settings['right_width'] = $normalized['right'];
    }

    $settings['footer_copyright'] = wp_kses_post($settings['footer_copyright']);
    $settings['show_footer_copyright'] = '1' === (string) $settings['show_footer_copyright'] ? '1' : '0';
    $settings['show_return_to_shop'] = '1' === (string) $settings['show_return_to_shop'] ? '1' : '0';

    return $settings;
}

/**
 * DEPRECATED FUNCTIONS
 *
 * The following functions have been moved to the BW_Social_Login class.
 * They are kept here as deprecated stubs for backward compatibility.
 * Use BW_Social_Login class methods directly instead.
 */

/**
 * @deprecated Use BW_Social_Login::start_oauth_flow() instead.
 */
function bw_mew_social_login_redirect($provider)
{
    // Deprecated: Handled by BW_Social_Login class.
}

/**
 * @deprecated Use BW_Social_Login::process_oauth_callback() instead.
 */
function bw_mew_process_social_login_callback($provider)
{
    // Deprecated: Handled by BW_Social_Login class.
}

/**
 * @deprecated Use BW_Social_Login::exchange_facebook_code() instead.
 */
function bw_mew_exchange_facebook_code($code, $redirect_uri)
{
    // Deprecated: Handled by BW_Social_Login class.
    return new WP_Error('bw_deprecated', __('This function is deprecated.', 'bw'));
}

/**
 * @deprecated Use BW_Social_Login::exchange_google_code() instead.
 */
function bw_mew_exchange_google_code($code, $redirect_uri)
{
    // Deprecated: Handled by BW_Social_Login class.
    return new WP_Error('bw_deprecated', __('This function is deprecated.', 'bw'));
}

/**
 * @deprecated Use BW_Social_Login::login_or_register_user() instead.
 */
function bw_mew_login_or_register_social_user($email, $name)
{
    // Deprecated: Handled by BW_Social_Login class.
    return new WP_Error('bw_deprecated', __('This function is deprecated.', 'bw'));
}

/**
 * Retrieve the passwordless URL if configured.
 *
 * @return string
 */
function bw_mew_get_passwordless_url()
{
    $url = get_option('bw_account_passwordless_url', '');

    if (!empty($url)) {
        return esc_url($url);
    }

    return '';
}

/**
 * Render minimal checkout header with logo and cart icon.
 */
function bw_mew_render_checkout_header()
{
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }

    // Get checkout settings
    $settings = bw_mew_get_checkout_settings();
    $logo_align = !empty($settings['logo_align']) ? $settings['logo_align'] : 'center';
    $logo_width = !empty($settings['logo_width']) ? absint($settings['logo_width']) : 200;
    $logo_padding_top = isset($settings['logo_padding_top']) ? absint($settings['logo_padding_top']) : 0;
    $logo_padding_right = isset($settings['logo_padding_right']) ? absint($settings['logo_padding_right']) : 0;
    $logo_padding_bottom = isset($settings['logo_padding_bottom']) ? absint($settings['logo_padding_bottom']) : 0;
    $logo_padding_left = isset($settings['logo_padding_left']) ? absint($settings['logo_padding_left']) : 0;

    // Get logo - prefer theme custom logo, fallback to checkout settings logo
    $logo_url = '';
    $home_url = home_url('/');

    if (function_exists('has_custom_logo') && has_custom_logo()) {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
            if ($logo_data) {
                $logo_url = $logo_data[0];
            }
        }
    }

    // Fallback to checkout settings logo if theme logo not available
    if (empty($logo_url)) {
        $logo_url = !empty($settings['logo']) ? $settings['logo'] : '';
    }

    // Get cart URL
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');

    // Build inline styles for logo
    $logo_styles = sprintf(
        'max-width: %dpx; padding: %dpx %dpx %dpx %dpx;',
        $logo_width,
        $logo_padding_top,
        $logo_padding_right,
        $logo_padding_bottom,
        $logo_padding_left
    );

    // Render header only if we have a logo
    if (!empty($logo_url)):
        ?>
        <div class="bw-minimal-checkout-header">
            <div
                class="bw-minimal-checkout-header__inner bw-minimal-checkout-header__inner--<?php echo esc_attr($logo_align); ?>">
                <a href="<?php echo esc_url($home_url); ?>" class="bw-minimal-checkout-header__logo"
                    style="<?php echo esc_attr($logo_styles); ?>">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" />
                </a>
                <a href="<?php echo esc_url($cart_url); ?>" class="bw-minimal-checkout-header__cart"
                    aria-label="<?php esc_attr_e('View cart', 'woocommerce'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="4" y="6" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.5" fill="none" />
                        <path d="M8 6V5C8 3.34315 9.34315 2 11 2H13C14.6569 2 16 3.34315 16 5V6" stroke="currentColor"
                            stroke-width="1.5" fill="none" />
                    </svg>
                </a>
            </div>
        </div>
        <?php
    endif;
}

/**
 * Render checkout logo header for order-received pages (logo only).
 *
 * Reuses checkout logo source and sizing options to keep visual consistency.
 *
 * @return void
 */
function bw_mew_render_order_received_logo_header()
{
    $settings = bw_mew_get_checkout_settings();
    $logo_width = !empty($settings['logo_width']) ? absint($settings['logo_width']) : 200;
    $logo_padding_top = isset($settings['logo_padding_top']) ? absint($settings['logo_padding_top']) : 0;
    $logo_padding_right = isset($settings['logo_padding_right']) ? absint($settings['logo_padding_right']) : 0;
    $logo_padding_bottom = isset($settings['logo_padding_bottom']) ? absint($settings['logo_padding_bottom']) : 0;
    $logo_padding_left = isset($settings['logo_padding_left']) ? absint($settings['logo_padding_left']) : 0;

    $logo_url = '';
    if (function_exists('has_custom_logo') && has_custom_logo()) {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
            if ($logo_data) {
                $logo_url = $logo_data[0];
            }
        }
    }

    if (empty($logo_url)) {
        $logo_url = !empty($settings['logo']) ? $settings['logo'] : '';
    }

    if (empty($logo_url)) {
        return;
    }

    $header_styles = 'position:absolute;top:0;left:0;width:100%;background:transparent;border:0;z-index:100;pointer-events:none;';
    $inner_styles  = 'width:100%;max-width:none;margin:0;padding:22px 28px;display:flex;align-items:center;justify-content:flex-start;';
    $anchor_styles = 'pointer-events:auto;display:inline-flex;align-items:center;justify-content:flex-start;text-decoration:none;';

    $logo_styles = sprintf(
        'max-width: %dpx; padding: %dpx %dpx %dpx %dpx;',
        $logo_width,
        $logo_padding_top,
        $logo_padding_right,
        $logo_padding_bottom,
        $logo_padding_left
    );
    ?>
    <div class="bw-minimal-checkout-header bw-minimal-checkout-header--order-received" style="<?php echo esc_attr($header_styles); ?>">
        <div class="bw-minimal-checkout-header__inner bw-minimal-checkout-header__inner--left" style="<?php echo esc_attr($inner_styles); ?>">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="bw-minimal-checkout-header__logo"
                style="<?php echo esc_attr($anchor_styles . $logo_styles); ?>">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" />
            </a>
        </div>
    </div>
    <?php
}

/**
 * Render free order banner when cart total is 0.
 * Note: Stripe Express Checkout provides its own "OR" separator.
 */
function bw_mew_render_express_divider()
{
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }

    // Check if cart total is 0
    $is_free = false;
    if (function_exists('WC') && WC()->cart) {
        $total = WC()->cart->get_total('edit');
        $is_free = (0 == $total || '0' === $total || 0.0 === (float) $total);
    }

    // Get free order message from settings
    $free_message = get_option('bw_checkout_free_order_message', '');
    if (empty($free_message)) {
        $free_message = __('Your order is free. Complete your details and click Place order.', 'bw');
    }

    if ($is_free) {
        // Render free order banner (visible immediately with bw-free-order-active class)
        $free_message_text = trim(wp_strip_all_tags((string) $free_message));
        if ('' === $free_message_text) {
            $free_message_text = __('Your order is free. Complete your details and click Place order.', 'bw');
        }
        ?>
        <div class="bw-free-order-banner bw-free-order-active">
            <div class="bw-free-order-banner__content">
                <p class="bw-free-order-banner__text"><?php echo esc_html($free_message_text); ?></p>
            </div>
        </div>
        <?php
    } else {
        // Render custom OR divider between Express buttons and form (only when order is NOT free)
        ?>
        <div class="bw-express-divider"><span><?php esc_html_e('OR', 'bw'); ?></span></div>
        <?php
    }
}
add_action('woocommerce_checkout_before_customer_details', 'bw_mew_render_express_divider', 100);

/**
 * AJAX handler to remove coupon from cart.
 *
 * FIX: This handler now properly synchronizes with WooCommerce's checkout refresh mechanism
 * to prevent the coupon from re-applying after removal. The key changes:
 * 1. Force WC session commit BEFORE responding to ensure session persistence
 * 2. Trigger WooCommerce's 'removed_coupon' action for proper event integration
 * 3. Return hash to help client detect if cart state changed
 */
function bw_mew_ajax_remove_coupon()
{
    check_ajax_referer('bw-checkout-nonce', 'nonce');

    if (!class_exists('WooCommerce') || !WC()->cart) {
        wp_send_json_error(array('message' => __('Cart not available.', 'woocommerce')));
    }

    $coupon_code = isset($_POST['coupon']) ? sanitize_text_field(wp_unslash($_POST['coupon'])) : '';

    if (empty($coupon_code)) {
        wp_send_json_error(array('message' => __('Invalid coupon code.', 'woocommerce')));
    }

    // Verify the coupon is actually applied before attempting removal
    $applied_coupons = WC()->cart->get_applied_coupons();
    if (!in_array(strtolower($coupon_code), array_map('strtolower', $applied_coupons), true)) {
        wp_send_json_error(array('message' => __('Coupon is not applied.', 'woocommerce')));
    }

    // Remove the coupon using WooCommerce's standard method
    $removed = WC()->cart->remove_coupon($coupon_code);

    if (!$removed) {
        wp_send_json_error(array('message' => __('Coupon could not be removed.', 'woocommerce')));
    }

    // Calculate totals after removing coupon
    WC()->cart->calculate_totals();

    // CRITICAL FIX: Force immediate session persistence with multiple safety checks
    // This ensures the coupon removal is saved BEFORE the checkout fragment refresh reads the session
    if (WC()->session) {
        // Save cart data to session immediately
        WC()->cart->persistent_cart_update();

        // Force session data write to database/storage
        WC()->session->save_data();

        // Additional safety: set the cart session explicitly
        WC()->session->set('cart', serialize(WC()->cart->get_cart_for_session()));
        WC()->session->set('applied_coupons', WC()->cart->get_applied_coupons());
        WC()->session->set('coupon_discount_totals', WC()->cart->get_coupon_discount_totals());
        WC()->session->set('coupon_discount_tax_totals', WC()->cart->get_coupon_discount_tax_totals());

        // Force one more save to commit all the above
        WC()->session->save_data();
    }

    // Clear any cart-related caches that might cause stale data
    wc_clear_notices();

    // Trigger WooCommerce's standard removed_coupon action for proper event integration
    // This allows other plugins/code to react to coupon removal
    do_action('woocommerce_removed_coupon', $coupon_code);

    wp_send_json_success(array(
        'message' => __('Coupon removed successfully.', 'woocommerce'),
        'applied_coupons' => WC()->cart->get_applied_coupons(),
        'cart_hash' => WC()->cart->get_cart_hash(), // Return hash to verify state change
    ));
}

/**
 * AJAX handler to apply coupon to cart.
 *
 * FIX: This custom handler ensures proper session persistence when applying coupons.
 * Without this, the coupon would be applied server-side but not reflected in the checkout
 * fragments due to race conditions between session writes and checkout refresh reads.
 *
 * Key differences from WooCommerce's standard apply_coupon endpoint:
 * 1. Forces immediate session persistence with multiple safety checks
 * 2. Explicitly sets all coupon-related session data
 * 3. Returns cart hash to help client detect state changes
 * 4. Properly integrates with WooCommerce's action hooks
 */
function bw_mew_ajax_apply_coupon()
{
    check_ajax_referer('bw-checkout-nonce', 'nonce');

    if (!class_exists('WooCommerce') || !WC()->cart) {
        wp_send_json_error(array('message' => __('Cart not available.', 'woocommerce')));
    }

    $coupon_code = isset($_POST['coupon_code']) ? sanitize_text_field(wp_unslash($_POST['coupon_code'])) : '';

    if (empty($coupon_code)) {
        wp_send_json_error(array('message' => __('Please enter a coupon code.', 'woocommerce')));
    }

    // Clear any existing notices to ensure clean validation
    wc_clear_notices();

    // Apply the coupon using WooCommerce's standard method
    $applied = WC()->cart->apply_coupon($coupon_code);

    if (!$applied) {
        // Get error message from WooCommerce notices
        $error_notices = wc_get_notices('error');
        $error_message = __('Invalid coupon code.', 'woocommerce');

        if (!empty($error_notices) && is_array($error_notices)) {
            $first_error = reset($error_notices);
            if (is_array($first_error) && isset($first_error['notice'])) {
                $error_message = wp_strip_all_tags((string) $first_error['notice']);
            } elseif (is_string($first_error)) {
                $error_message = wp_strip_all_tags($first_error);
            }
        }

        wc_clear_notices();
        wp_send_json_error(array('message' => $error_message));
    }

    // Calculate totals after applying coupon
    WC()->cart->calculate_totals();

    // CRITICAL FIX: Force immediate session persistence with multiple safety checks
    // This ensures the coupon application is saved BEFORE the checkout fragment refresh reads the session
    if (WC()->session) {
        // Save cart data to session immediately
        WC()->cart->persistent_cart_update();

        // Force session data write to database/storage
        WC()->session->save_data();

        // Additional safety: set the cart session explicitly
        WC()->session->set('cart', serialize(WC()->cart->get_cart_for_session()));
        WC()->session->set('applied_coupons', WC()->cart->get_applied_coupons());
        WC()->session->set('coupon_discount_totals', WC()->cart->get_coupon_discount_totals());
        WC()->session->set('coupon_discount_tax_totals', WC()->cart->get_coupon_discount_tax_totals());

        // Force one more save to commit all the above
        WC()->session->save_data();
    }

    // Clear any notices
    wc_clear_notices();

    // Trigger WooCommerce's standard applied_coupon action for proper event integration
    // This allows other plugins/code to react to coupon application
    do_action('woocommerce_applied_coupon', $coupon_code);

    wp_send_json_success(array(
        'message' => __('Coupon applied successfully.', 'woocommerce'),
        'applied_coupons' => WC()->cart->get_applied_coupons(),
        'cart_hash' => WC()->cart->get_cart_hash(), // Return hash to verify state change
    ));
}

/**
 * Hide PayPal Advanced Card Processing gateway (duplicate of Stripe).
 * We use Stripe for all card payments.
 *
 * @param array $available_gateways Available payment gateways.
 * @return array Filtered payment gateways.
 */
function bw_mew_hide_paypal_advanced_card_processing($available_gateways)
{
    if (!is_admin() && is_checkout()) {
        // Remove PayPal Advanced Card Processing (ppcp-credit-card-gateway)
        // This is a duplicate of Stripe and causes confusion
        if (isset($available_gateways['ppcp-credit-card-gateway'])) {
            unset($available_gateways['ppcp-credit-card-gateway']);
        }

        // Also remove WooCommerce Payments if present (another duplicate)
        if (isset($available_gateways['woocommerce_payments'])) {
            unset($available_gateways['woocommerce_payments']);
        }
    }

    return $available_gateways;
}

/**
 * Customize Stripe Elements styling to match Shopify design.
 * Uses WooCommerce Stripe Gateway filter to configure appearance.
 *
 * @param array $options Stripe Elements options.
 * @return array Modified options with custom styling.
 */
function bw_mew_customize_stripe_elements_style($options)
{
    // Ensure 8px border radius for inputs (Legacy Style API)
    $style = array(
        'base' => array(
            'color' => '#1f2937',
            'fontFamily' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'fontSize' => '15px',
            '::placeholder' => array(
                'color' => '#9ca3af',
            ),
        ),
        'invalid' => array(
            'color' => '#991b1b',
        ),
    );

    // If this is the 'styling' filter, return just the style array
    if (current_filter() === 'wc_stripe_elements_styling') {
        return $style;
    }

    // Otherwise, assume it's 'elements_options' and add Appearance API (UPE/Modern)
    $options['style'] = $style;
    $options['appearance'] = array(
        'theme' => 'flat',
        'variables' => array(
            'colorPrimary' => '#000000',
            'colorBackground' => '#ffffff',
            'colorText' => '#1f2937',
            'colorDanger' => '#991b1b',
            'colorSuccess' => '#27ae60',
            'fontFamily' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'fontSizeBase' => '15px',
            'fontWeightNormal' => '400',
            'fontWeightMedium' => '500',
            'borderRadius' => '0px',
            'spacingUnit' => '4px',
            'gridRowGap' => '20px',
            'gridColumnGap' => '12px',
        ),
        'rules' => array(
            '.Input' => array(
                'border' => '1px solid #d1d5db',
                'boxShadow' => 'none',
                'padding' => '16px 18px',
                'borderRadius' => '8px',
            ),
            '.Label' => array(
                'display' => 'block',
                'textAlign' => 'center',
                'marginBottom' => '8px',
            ),
            '.Block' => array(
                'backgroundColor' => 'transparent',
                'border' => 'none',
                'boxShadow' => 'none',
                'padding' => '0',
                'margin' => '0',
            ),
            '.PaymentElement' => array(
                'padding' => '0',
            ),
        ),
    );

    return $options;
}

/**
 * Customize Stripe UPE (Unified Payment Element) appearance.
 * This filter modifies the JavaScript params passed to Stripe UPE iframe.
 *
 * @param array $params UPE initialization params.
 * @return array Modified params with custom appearance.
 */
function bw_mew_customize_stripe_upe_appearance($params)
{
    // Add appearance configuration to UPE params
    $params['appearance'] = array(
        'theme' => 'flat',
        'variables' => array(
            'colorPrimary' => '#000000',
            'colorBackground' => '#ffffff',
            'colorText' => '#1f2937',
            'colorDanger' => '#991b1b',
            'colorSuccess' => '#27ae60',
            'fontFamily' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'fontSizeBase' => '15px',
            'fontWeightNormal' => '400',
            'fontWeightMedium' => '500',
            'borderRadius' => '0px',
            'spacingUnit' => '4px',
            'gridRowGap' => '20px',
            'gridColumnGap' => '12px',
        ),
        'rules' => array(
            '.Input' => array(
                'border' => '1px solid #d1d5db',
                'boxShadow' => 'none',
                'padding' => '16px 18px',
                'borderRadius' => '8px',
            ),
            '.Input:hover' => array(
                'borderColor' => '#9ca3af',
            ),
            '.Input:focus' => array(
                'borderColor' => '#3b82f6',
                'boxShadow' => '0 0 0 3px rgba(59, 130, 246, 0.1)',
            ),
            '.Input--invalid' => array(
                'borderColor' => '#fecaca',
                'backgroundColor' => '#fef2f2',
            ),
            '.Label' => array(
                'display' => 'block',
                'textAlign' => 'center',
                'marginBottom' => '8px',
            ),
            '.Block' => array(
                'backgroundColor' => 'transparent',
                'border' => 'none',
                'boxShadow' => 'none',
                'padding' => '0',
                'margin' => '0',
            ),
            '.BlockItem' => array(
                'backgroundColor' => 'transparent',
                'border' => 'none',
                'boxShadow' => 'none',
                'padding' => '0',
                'margin' => '0',
            ),
            // FIX 1: Error message icon positioning - use !important to override Stripe defaults
            '.Error' => array(
                'display' => 'flex',
                'flexDirection' => 'row',
                'alignItems' => 'flex-start',
                'gap' => '8px',
                'marginTop' => '8px',
                'fontSize' => '13px',
                'lineHeight' => '1.4',
                'color' => '#991b1b',
            ),
            '.ErrorIcon' => array(
                'flexShrink' => '0',
                'width' => '16px',
                'height' => '16px',
                'minWidth' => '16px',
                'marginTop' => '2px',
                'marginRight' => '0',
                'marginBottom' => '0',
                'marginLeft' => '0',
                'display' => 'inline-flex',
            ),
            '.ErrorText' => array(
                'flex' => '1 1 auto',
                'marginTop' => '0',
                'marginRight' => '0',
                'marginBottom' => '0',
                'marginLeft' => '0',
                'display' => 'inline-block',
            ),
            '.Tab' => array(
                'display' => 'none',
            ),
            '.TabLabel' => array(
                'display' => 'none',
            ),
            '.TabIcon' => array(
                'display' => 'none',
            ),
            '.Accordion' => array(
                'border' => 'none',
                'boxShadow' => 'none',
                'padding' => '0',
                'margin' => '0 !important',
            ),
            '.AccordionItem' => array(
                'border' => 'none',
                'boxShadow' => 'none',
                'backgroundColor' => 'transparent',
                'padding' => '0',
                'margin' => '0 !important',
            ),
            '.AccordionItemHeader' => array(
                'display' => 'none',
            ),
            '.PickerItem' => array(
                'display' => 'none',
            ),
            '.PaymentMethod' => array(
                'padding' => '0',
                'border' => 'none',
                'margin' => '0',
            ),
            '.PaymentMethodHeader' => array(
                'display' => 'none !important',
            ),
            '.PaymentElement' => array(
                'padding' => '0',
            ),
            '.AccordionButton' => array(
                'display' => 'none !important',
            ),
        ),
    );

    // FIX 3: Configure fields to auto-collect billing details from WooCommerce checkout form
    // This prevents the "You specified 'never' but did not pass billing_details.name" error
    $params['fields'] = array(
        'billingDetails' => array(
            'name' => 'auto',    // Auto-collect from WC checkout form
            'email' => 'auto',   // Auto-collect from WC checkout form
            'phone' => 'auto',   // Auto-collect from WC checkout form
            'address' => array(
                'country' => 'auto',
                'line1' => 'auto',
                'line2' => 'auto',
                'city' => 'auto',
                'state' => 'auto',
                'postalCode' => 'auto',
            ),
        ),
    );

    return $params;
}

/**
 * Add body classes for section heading customizations.
 *
 * @param array $classes Body classes.
 * @return array
 */
function bw_mew_add_section_heading_body_classes($classes)
{
    if (!is_checkout() || is_wc_endpoint_url()) {
        return $classes;
    }

    $hide_billing = get_option('bw_checkout_hide_billing_heading', '0');
    $hide_additional = get_option('bw_checkout_hide_additional_heading', '0');

    if ('1' === $hide_billing) {
        $classes[] = 'bw-hide-billing-heading';
    }

    if ('1' === $hide_additional) {
        $classes[] = 'bw-hide-additional-heading';
    }

    return $classes;
}

/**
 * Render custom address section heading before checkout customer details.
 */
function bw_mew_render_address_section_heading()
{
    $label = get_option('bw_checkout_address_heading_label', '');

    if (empty($label)) {
        return;
    }

    echo '<h3 class="bw-checkout-section-heading bw-checkout-section-heading--delivery">' . esc_html($label) . '</h3>';
}

/**
 * Add BlackWork custom gateways to WooCommerce payment gateways.
 *
 * @param array $gateways WooCommerce gateways.
 * @return array
 */
function bw_mew_add_google_pay_gateway($gateways)
{
    if (class_exists('BW_Google_Pay_Gateway')) {
        $gateways[] = 'BW_Google_Pay_Gateway';
    }
    if (class_exists('BW_Klarna_Gateway')) {
        $gateways[] = 'BW_Klarna_Gateway';
    }
    return $gateways;
}

/**
 * Enqueue assets for the custom cart layout.
 */
function bw_mew_enqueue_cart_assets()
{
    if (!function_exists('is_cart') || !is_cart()) {
        return;
    }

    $css_file = BW_MEW_PATH . 'assets/css/bw-cart.css';
    $js_file = BW_MEW_PATH . 'assets/js/bw-cart.js';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_enqueue_style(
        'bw-cart',
        BW_MEW_URL . 'assets/css/bw-cart.css',
        [],
        $css_version
    );

    wp_enqueue_script(
        'bw-cart',
        BW_MEW_URL . 'assets/js/bw-cart.js',
        ['jquery'],
        $js_version,
        true
    );
}

/**
 * Prepare cart layout by hiding theme elements if necessary.
 */
function bw_mew_prepare_cart_layout()
{
    if (!function_exists('is_cart') || !is_cart()) {
        return;
    }

    add_filter('woocommerce_show_page_title', '__return_false');

    // Unhook cross-sells from their default position in collaterals
    remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');

    // Remove shipping from cart totals
    add_filter('woocommerce_cart_ready_to_calc_shipping', '__return_false', 99);
    add_filter('woocommerce_shipping_calculator_enabled', '__return_false', 99);
    add_filter('woocommerce_cart_needs_shipping', '__return_false', 99);
}
