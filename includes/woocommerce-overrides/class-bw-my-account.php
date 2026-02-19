<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Custom My Account layout helpers and hooks.
 */

/**
 * Filter WooCommerce account menu items to keep only the desired entries.
 *
 * @param array $items Menu items.
 *
 * @return array
 */
function bw_mew_filter_account_menu_items( $items ) {
    $order          = [ 'dashboard', 'downloads', 'orders', 'edit-account', 'customer-logout' ];
    $filtered_items = [];

    foreach ( $order as $endpoint ) {
        if ( 'orders' === $endpoint ) {
            $label = __( 'My purchases', 'bw' );
        } else {
            if ( ! isset( $items[ $endpoint ] ) ) {
                continue;
            }

            $label = $items[ $endpoint ];
            if ( 'edit-account' === $endpoint ) {
                $label = __( 'settings', 'bw' );
            } elseif ( 'customer-logout' === $endpoint ) {
                $label = __( 'logout', 'bw' );
            }
        }

        $filtered_items[ $endpoint ] = $label;
    }

    return $filtered_items;
}
add_filter( 'woocommerce_account_menu_items', 'bw_mew_filter_account_menu_items', 20 );

/**
 * Append logged_out flag after logout redirects.
 *
 * @param string  $redirect_to Redirect URL.
 * @param string  $requested   Requested redirect URL.
 * @param WP_User $user        User object.
 *
 * @return string
 */
function bw_mew_append_logout_flag( $redirect_to, $requested, $user ) {
    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );

    if ( ! $redirect_to ) {
        $redirect_to = $account_url;
    }

    if ( $account_url && false !== strpos( $redirect_to, $account_url ) ) {
        $redirect_to = add_query_arg( 'logged_out', '1', $redirect_to );
    }

    return $redirect_to;
}
add_filter( 'logout_redirect', 'bw_mew_append_logout_flag', 10, 3 );

/**
 * Check if a user still needs onboarding.
 *
 * @param int $user_id User ID.
 *
 * @return bool
 */
function bw_user_needs_onboarding( $user_id ) {
    if ( ! $user_id ) {
        return false;
    }

    return 1 !== (int) get_user_meta( $user_id, 'bw_supabase_onboarded', true );
}

/**
 * Register the set-password endpoint under My Account.
 */
function bw_mew_register_set_password_endpoint() {
    add_rewrite_endpoint( 'set-password', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'bw_mew_register_set_password_endpoint' );

/**
 * Add the set-password query var to WooCommerce endpoints.
 *
 * @param array $vars WooCommerce query vars.
 *
 * @return array
 */
function bw_mew_add_set_password_query_var( $vars ) {
    $vars['set-password'] = 'set-password';

    return $vars;
}
add_filter( 'woocommerce_get_query_vars', 'bw_mew_add_set_password_query_var' );

/**
 * Render set-password endpoint content.
 */
function bw_mew_render_set_password_endpoint() {
    wc_get_template( 'myaccount/set-password.php' );
}
add_action( 'woocommerce_account_set-password_endpoint', 'bw_mew_render_set_password_endpoint' );

/**
 * Enforce onboarding lock until Supabase password is set.
 *
 * When provider is Supabase, we use a modal instead of redirecting.
 * When provider is WordPress, no onboarding lock is enforced.
 */
function bw_mew_enforce_supabase_onboarding_lock() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_user_logged_in() ) {
        return;
    }

    $provider = get_option( 'bw_account_login_provider', 'wordpress' );

    // If provider is WordPress, no onboarding lock
    if ( 'wordpress' === $provider ) {
        return;
    }

    // For Supabase provider: if user is onboarded, redirect away from set-password
    if ( ! bw_user_needs_onboarding( get_current_user_id() ) ) {
        if ( is_wc_endpoint_url( 'set-password' ) ) {
            wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
            exit;
        }
        return;
    }

    // For Supabase provider: let modal handle gating (no redirect)
    // Allow set-password and logout endpoints
    if ( is_wc_endpoint_url( 'set-password' ) || is_wc_endpoint_url( 'customer-logout' ) ) {
        return;
    }

    // Modal will handle the gating via JS - no redirect needed
}
add_action( 'template_redirect', 'bw_mew_enforce_supabase_onboarding_lock' );

/**
 * Normalize guest email-entry URLs to My Account root.
 *
 * When users click email CTAs before login, endpoint URLs (for example
 * /my-account/downloads/) may show a generic logged-out state. Force a stable
 * entry on /my-account/ while preserving post-checkout query args so the
 * custom invite/resend guidance is always visible.
 */
function bw_mew_normalize_guest_email_entrypoint() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
        return;
    }

    $target = isset( $_GET['bw_after_login'] ) ? sanitize_key( wp_unslash( $_GET['bw_after_login'] ) ) : '';
    if ( ! in_array( $target, [ 'orders', 'downloads' ], true ) ) {
        return;
    }

    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );

    if ( ! $account_url ) {
        return;
    }

    $current_path = wp_parse_url( (string) wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
    $account_path = wp_parse_url( $account_url, PHP_URL_PATH );
    if ( $current_path && $account_path && untrailingslashit( $current_path ) === untrailingslashit( $account_path ) ) {
        return;
    }

    $query_args = [
        'bw_after_login' => $target,
    ];

    $is_post_checkout = isset( $_GET['bw_post_checkout'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['bw_post_checkout'] ) );
    if ( $is_post_checkout ) {
        $query_args['bw_post_checkout'] = '1';
    }

    $invite_email = isset( $_GET['bw_invite_email'] ) ? sanitize_email( wp_unslash( $_GET['bw_invite_email'] ) ) : '';
    if ( $invite_email ) {
        $query_args['bw_invite_email'] = $invite_email;
    }

    wp_safe_redirect( add_query_arg( $query_args, $account_url ) );
    exit;
}
add_action( 'template_redirect', 'bw_mew_normalize_guest_email_entrypoint', 15 );

/**
 * Handle post-login email entrypoint redirects from bw_after_login query arg.
 *
 * Supports safe endpoints used by email CTAs:
 * - orders
 * - downloads
 */
function bw_mew_handle_email_entrypoint_redirect() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
        return;
    }

    $target = isset( $_GET['bw_after_login'] ) ? sanitize_key( wp_unslash( $_GET['bw_after_login'] ) ) : '';
    if ( ! in_array( $target, [ 'orders', 'downloads' ], true ) ) {
        return;
    }

    if ( ! is_user_logged_in() ) {
        return;
    }

    $provider = get_option( 'bw_account_login_provider', 'wordpress' );
    if ( 'supabase' === $provider && function_exists( 'bw_user_needs_onboarding' ) && bw_user_needs_onboarding( get_current_user_id() ) ) {
        // Keep user on My Account so modal can complete setup first.
        return;
    }

    $target_url = wc_get_account_endpoint_url( $target );
    if ( ! $target_url ) {
        return;
    }

    wp_safe_redirect( $target_url );
    exit;
}
add_action( 'template_redirect', 'bw_mew_handle_email_entrypoint_redirect', 20 );

/**
 * Optional redirect from order-received to My Account for guest users.
 *
 * Default behavior is disabled to keep users on the Thank You page after checkout.
 * Enable only via filter:
 * add_filter( 'bw_mew_redirect_guest_order_received_to_account', '__return_true' );
 */
function bw_mew_redirect_order_verify_email_for_supabase() {
    if ( is_user_logged_in() ) {
        return;
    }

    if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
        return;
    }

    $provider = get_option( 'bw_account_login_provider', 'wordpress' );
    if ( 'supabase' !== $provider ) {
        return;
    }

    $should_redirect = (bool) apply_filters( 'bw_mew_redirect_guest_order_received_to_account', false );
    if ( ! $should_redirect ) {
        return;
    }

    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );

    if ( ! $account_url ) {
        return;
    }

    wp_safe_redirect( $account_url );
    exit;
}
add_action( 'template_redirect', 'bw_mew_redirect_order_verify_email_for_supabase', 25 );

/**
 * Force callback loader entry for unauthenticated invite/set-password transitions.
 *
 * This avoids rendering the logged-out My Account form during the short interval
 * before WP auth cookie/session is fully available.
 */
function bw_mew_force_auth_callback_for_guest_transitions() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
        return;
    }

    $is_callback = isset( $_GET['bw_auth_callback'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['bw_auth_callback'] ) );
    if ( $is_callback ) {
        return;
    }

    $needs_set_password = isset( $_GET['bw_set_password'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['bw_set_password'] ) );
    $auth_code          = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
    $auth_type          = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
    $is_auth_transition = ! empty( $auth_code ) && in_array( $auth_type, [ 'invite', 'recovery' ], true );

    if ( ! $needs_set_password && ! $is_auth_transition ) {
        return;
    }

    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );
    if ( ! $account_url ) {
        return;
    }

    $target_url = add_query_arg( 'bw_auth_callback', '1', $account_url );

    if ( $needs_set_password ) {
        $target_url = add_query_arg( 'bw_set_password', '1', $target_url );
    }

    if ( $is_auth_transition ) {
        $target_url = add_query_arg(
            [
                'code' => $auth_code,
                'type' => $auth_type,
            ],
            $target_url
        );

        $state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
        if ( '' !== $state ) {
            $target_url = add_query_arg( 'state', $state, $target_url );
        }

        $provider = isset( $_GET['provider'] ) ? sanitize_key( wp_unslash( $_GET['provider'] ) ) : '';
        if ( '' !== $provider ) {
            $target_url = add_query_arg( 'provider', $provider, $target_url );
        }
    }

    wp_safe_redirect( $target_url );
    exit;
}
add_action( 'template_redirect', 'bw_mew_force_auth_callback_for_guest_transitions', 6 );

/**
 * Clean stale auth-callback query from logged-in account sessions.
 */
function bw_mew_cleanup_logged_in_auth_callback_query() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_user_logged_in() ) {
        return;
    }

    $is_callback = isset( $_GET['bw_auth_callback'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['bw_auth_callback'] ) );
    if ( ! $is_callback ) {
        return;
    }

    if ( is_wc_endpoint_url( 'set-password' ) ) {
        return;
    }

    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );
    if ( ! $account_url ) {
        return;
    }

    wp_safe_redirect( $account_url );
    exit;
}
add_action( 'template_redirect', 'bw_mew_cleanup_logged_in_auth_callback_query', 7 );

/**
 * Enqueue assets for the logged-in my account area.
 */
function bw_mew_enqueue_my_account_assets() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
        return;
    }

    $load_full_account_assets = is_user_logged_in() || is_wc_endpoint_url( 'set-password' );

    $css_file = BW_MEW_PATH . 'assets/css/bw-my-account.css';
    $css_ver  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';
    $js_file  = BW_MEW_PATH . 'assets/js/bw-my-account.js';
    $js_ver   = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

    if ( $load_full_account_assets ) {
        wp_enqueue_style(
            'bw-my-account',
            BW_MEW_URL . 'assets/css/bw-my-account.css',
            [],
            $css_ver
        );
    }

    wp_enqueue_script(
        'bw-my-account',
        BW_MEW_URL . 'assets/js/bw-my-account.js',
        [],
        $js_ver,
        true
    );

    wp_localize_script(
        'bw-my-account',
        'bwAccountOnboarding',
        [
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'bw-supabase-login' ),
            'projectUrl'  => get_option( 'bw_supabase_project_url', '' ),
            'anonKey'     => get_option( 'bw_supabase_anon_key', '' ),
            'setPasswordUrl' => wc_get_account_endpoint_url( 'set-password' ),
            'redirectUrl' => wc_get_page_permalink( 'myaccount' ),
            'debug'       => (bool) get_option( 'bw_supabase_debug_log', 0 ),
            'userEmail'   => is_user_logged_in() ? wp_get_current_user()->user_email : '',
        ]
    );
}
add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_my_account_assets', 25 );

/**
 * Handle profile updates for WooCommerce account settings.
 */
function bw_mew_handle_profile_update() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_user_logged_in() ) {
        return;
    }

    $submit_profile  = ! empty( $_POST['bw_account_profile_submit'] );
    $submit_billing  = ! empty( $_POST['bw_account_billing_submit'] );
    $submit_shipping = ! empty( $_POST['bw_account_shipping_submit'] );

    if ( ! $submit_profile && ! $submit_billing && ! $submit_shipping ) {
        return;
    }

    if ( empty( $_POST['bw-profile-details-nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['bw-profile-details-nonce'] ), 'bw_save_profile_details' ) ) {
        wc_add_notice( __( 'Unable to save your profile. Please try again.', 'bw' ), 'error' );
        return;
    }

    $user_id = get_current_user_id();
    $errors  = new WP_Error();
    $customer = new WC_Customer( $user_id );
    $countries = new WC_Countries();

    if ( $submit_profile ) {
        $first_name   = isset( $_POST['account_first_name'] ) ? wc_clean( wp_unslash( $_POST['account_first_name'] ) ) : '';
        $last_name    = isset( $_POST['account_last_name'] ) ? wc_clean( wp_unslash( $_POST['account_last_name'] ) ) : '';
        $display_name = isset( $_POST['account_display_name'] ) ? wc_clean( wp_unslash( $_POST['account_display_name'] ) ) : '';

        if ( ! $first_name ) {
            $errors->add( 'first_name', __( 'Please enter your first name.', 'bw' ) );
        }

        if ( ! $last_name ) {
            $errors->add( 'last_name', __( 'Please enter your last name.', 'bw' ) );
        }

        if ( ! $display_name ) {
            $errors->add( 'display_name', __( 'Please enter a display name.', 'bw' ) );
        }
    }

    $billing_values = [];
    if ( $submit_billing ) {
        $billing_country  = isset( $_POST['billing_country'] ) ? wc_clean( wp_unslash( $_POST['billing_country'] ) ) : '';
        $billing_fields   = $countries->get_address_fields( $billing_country, 'billing_' );

        foreach ( $billing_fields as $key => $field ) {
            $value = isset( $_POST[ $key ] ) ? wc_clean( wp_unslash( $_POST[ $key ] ) ) : '';
            if ( ! empty( $field['required'] ) && '' === $value ) {
                $errors->add( $key, sprintf( __( 'Please enter %s.', 'bw' ), $field['label'] ?? $key ) );
            }
            $billing_values[ $key ] = $value;
        }
    }

    $shipping_values = [];
    if ( $submit_shipping ) {
        $ship_to_billing = ! empty( $_POST['shipping_same_as_billing'] );
        if ( $ship_to_billing ) {
            foreach ( $countries->get_address_fields( $customer->get_billing_country(), 'billing_' ) as $key => $field ) {
                $billing_value = $customer->{'get_' . $key}();
                $shipping_key  = str_replace( 'billing_', 'shipping_', $key );
                $shipping_values[ $shipping_key ] = $billing_value;
            }
        } else {
            $shipping_country = isset( $_POST['shipping_country'] ) ? wc_clean( wp_unslash( $_POST['shipping_country'] ) ) : '';
            $shipping_fields  = $countries->get_address_fields( $shipping_country, 'shipping_' );

            foreach ( $shipping_fields as $key => $field ) {
                $value = isset( $_POST[ $key ] ) ? wc_clean( wp_unslash( $_POST[ $key ] ) ) : '';
                if ( ! empty( $field['required'] ) && '' === $value ) {
                    $errors->add( $key, sprintf( __( 'Please enter %s.', 'bw' ), $field['label'] ?? $key ) );
                }
                $shipping_values[ $key ] = $value;
            }
        }
    }

    if ( $errors->has_errors() ) {
        foreach ( $errors->get_error_messages() as $message ) {
            wc_add_notice( $message, 'error' );
        }
        return;
    }

    if ( $submit_profile ) {
        update_user_meta( $user_id, 'first_name', $first_name );
        update_user_meta( $user_id, 'last_name', $last_name );
        wp_update_user(
            [
                'ID'           => $user_id,
                'display_name' => $display_name,
            ]
        );
        wc_add_notice( __( 'Profile updated.', 'bw' ), 'success' );
    }

    if ( $submit_billing ) {
        foreach ( $billing_values as $key => $value ) {
            $setter = 'set_' . $key;
            if ( method_exists( $customer, $setter ) ) {
                $customer->{$setter}( $value );
            } else {
                update_user_meta( $user_id, $key, $value );
            }
        }
        $customer->save();
        wc_add_notice( __( 'Billing details updated.', 'bw' ), 'success' );
    }

    if ( $submit_shipping ) {
        foreach ( $shipping_values as $key => $value ) {
            $setter = 'set_' . $key;
            if ( method_exists( $customer, $setter ) ) {
                $customer->{$setter}( $value );
            } else {
                update_user_meta( $user_id, $key, $value );
            }
        }
        $customer->save();
        wc_add_notice( __( 'Shipping details updated.', 'bw' ), 'success' );
    }

    wp_safe_redirect( wc_get_account_endpoint_url( 'edit-account' ) );
    exit;
}
add_action( 'template_redirect', 'bw_mew_handle_profile_update', 12 );

/**
 * Helper to get the black box text content.
 *
 * @return string
 */
function bw_mew_get_my_account_black_box_text() {
    $default = __( 'Your mockups will always be here, available to download. Please enjoy them!', 'bw' );

    return get_option( 'bw_myaccount_black_box_text', $default );
}

/**
 * Get My Account support link URL from settings.
 *
 * @return string
 */
function bw_mew_get_my_account_support_link() {
    $default = function_exists( 'wc_get_endpoint_url' ) ? wc_get_endpoint_url( 'edit-account' ) : home_url( '/contact/' );
    $url     = (string) get_option( 'bw_myaccount_support_link', $default );
    $url     = trim( $url );

    if ( '' === $url ) {
        return $default;
    }

    return esc_url_raw( $url );
}

/**
 * Get display name parts for My Account dashboard.
 *
 * @param int $user_id User ID.
 *
 * @return array{full_name:string,email:string}
 */
function bw_mew_get_dashboard_identity( $user_id ) {
    $user = get_user_by( 'id', $user_id );
    if ( ! $user instanceof WP_User ) {
        return [
            'full_name' => '',
            'email'     => '',
        ];
    }

    $first_name = trim( (string) get_user_meta( $user_id, 'first_name', true ) );
    $last_name  = trim( (string) get_user_meta( $user_id, 'last_name', true ) );

    if ( '' === $first_name ) {
        $first_name = trim( (string) get_user_meta( $user_id, 'billing_first_name', true ) );
    }
    if ( '' === $last_name ) {
        $last_name = trim( (string) get_user_meta( $user_id, 'billing_last_name', true ) );
    }

    if ( '' === $first_name || '' === $last_name ) {
        $orders = wc_get_orders(
            [
                'limit'    => 1,
                'customer' => $user_id,
                'orderby'  => 'date',
                'order'    => 'DESC',
                'return'   => 'objects',
            ]
        );

        if ( ! empty( $orders ) && $orders[0] instanceof WC_Order ) {
            if ( '' === $first_name ) {
                $first_name = trim( (string) $orders[0]->get_billing_first_name() );
            }
            if ( '' === $last_name ) {
                $last_name = trim( (string) $orders[0]->get_billing_last_name() );
            }
        }
    }

    $full_name = trim( $first_name . ' ' . $last_name );

    return [
        'full_name' => $full_name,
        'email'     => sanitize_email( (string) $user->user_email ),
    ];
}

/**
 * Sync missing profile names from billing fields / latest order on My Account.
 *
 * @return void
 */
function bw_mew_sync_profile_names_from_purchase_data() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_user_logged_in() || ! function_exists( 'wc_get_orders' ) ) {
        return;
    }

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return;
    }

    $user = get_user_by( 'id', $user_id );
    if ( ! $user instanceof WP_User ) {
        return;
    }

    $first_name = trim( (string) get_user_meta( $user_id, 'first_name', true ) );
    $last_name  = trim( (string) get_user_meta( $user_id, 'last_name', true ) );
    $changed    = false;

    if ( '' === $first_name ) {
        $billing_first = trim( (string) get_user_meta( $user_id, 'billing_first_name', true ) );
        if ( '' !== $billing_first ) {
            $first_name = $billing_first;
            update_user_meta( $user_id, 'first_name', $billing_first );
            $changed = true;
        }
    }

    if ( '' === $last_name ) {
        $billing_last = trim( (string) get_user_meta( $user_id, 'billing_last_name', true ) );
        if ( '' !== $billing_last ) {
            $last_name = $billing_last;
            update_user_meta( $user_id, 'last_name', $billing_last );
            $changed = true;
        }
    }

    if ( '' === $first_name || '' === $last_name ) {
        $orders = wc_get_orders(
            [
                'limit'    => 1,
                'customer' => $user_id,
                'orderby'  => 'date',
                'order'    => 'DESC',
                'return'   => 'objects',
            ]
        );

        if ( ! empty( $orders ) && $orders[0] instanceof WC_Order ) {
            if ( '' === $first_name ) {
                $order_first = trim( (string) $orders[0]->get_billing_first_name() );
                if ( '' !== $order_first ) {
                    $first_name = $order_first;
                    update_user_meta( $user_id, 'first_name', $order_first );
                    $changed = true;
                }
            }

            if ( '' === $last_name ) {
                $order_last = trim( (string) $orders[0]->get_billing_last_name() );
                if ( '' !== $order_last ) {
                    $last_name = $order_last;
                    update_user_meta( $user_id, 'last_name', $order_last );
                    $changed = true;
                }
            }
        }
    }

    $full_name    = trim( $first_name . ' ' . $last_name );
    $display_name = trim( (string) $user->display_name );
    $login_name   = trim( (string) $user->user_login );
    $email_name   = trim( (string) $user->user_email );
    $should_set_display = '' !== $full_name
        && ( '' === $display_name || $display_name === $login_name || $display_name === $email_name );

    if ( $should_set_display ) {
        wp_update_user(
            [
                'ID'           => $user_id,
                'display_name' => $full_name,
            ]
        );
    } elseif ( ! $changed ) {
        return;
    }
}
add_action( 'template_redirect', 'bw_mew_sync_profile_names_from_purchase_data', 11 );

/**
 * Resolve license label for an order item.
 *
 * @param WC_Order_Item_Product $item    Order item.
 * @param WC_Product|null       $product Product.
 *
 * @return string
 */
function bw_mew_get_order_item_license_label( WC_Order_Item_Product $item, $product = null ) {
    $license = '';

    $variation_id = (int) $item->get_variation_id();
    if ( $variation_id > 0 ) {
        $variation_product = $product instanceof WC_Product_Variation ? $product : wc_get_product( $variation_id );
        if ( $variation_product instanceof WC_Product_Variation ) {
            $variation_attributes = $variation_product->get_variation_attributes();

            $preferred_keys = [ 'attribute_pa_license', 'attribute_license' ];
            foreach ( $preferred_keys as $key ) {
                if ( ! empty( $variation_attributes[ $key ] ) ) {
                    $license = bw_mew_format_license_label( (string) $variation_attributes[ $key ] );
                    break;
                }
            }

            if ( '' === $license ) {
                foreach ( $variation_attributes as $value ) {
                    if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
                        $license = bw_mew_format_license_label( (string) $value );
                        break;
                    }
                }
            }
        }
    }

    $license_keys = [ 'attribute_pa_license', 'attribute_license', 'pa_license', 'license', 'license_type', '_license', '_license_type', 'License', 'License Type' ];
    if ( '' === $license ) {
        foreach ( $license_keys as $key ) {
            $value = $item->get_meta( $key, true );
            if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
                $license = bw_mew_format_license_label( (string) $value );
                break;
            }
        }
    }

    if ( '' === $license ) {
        if ( $variation_id > 0 && function_exists( 'wc_get_product_variation_attributes' ) ) {
            $variation_attributes = wc_get_product_variation_attributes( $variation_id );
            $variation_value      = $variation_attributes['attribute_pa_license'] ?? ( $variation_attributes['attribute_license'] ?? '' );
            if ( is_scalar( $variation_value ) && '' !== trim( (string) $variation_value ) ) {
                $license = bw_mew_format_license_label( (string) $variation_value );
            }
        }
    }

    if ( '' === $license && $product instanceof WC_Product ) {
        $attribute = (string) $product->get_attribute( 'pa_license' );
        if ( '' !== trim( $attribute ) ) {
            $license = bw_mew_format_license_label( $attribute );
        }
    }

    return $license ? $license : __( 'License', 'bw' );
}

/**
 * Format stored license value into a readable label.
 *
 * @param string $value Raw license value.
 *
 * @return string
 */
function bw_mew_format_license_label( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) {
        return '';
    }

    $decoded = rawurldecode( $value );
    $taxonomies = [ 'pa_license', 'license' ];

    foreach ( $taxonomies as $taxonomy ) {
        if ( ! taxonomy_exists( $taxonomy ) ) {
            continue;
        }

        $term = get_term_by( 'slug', $decoded, $taxonomy );
        if ( $term && ! is_wp_error( $term ) && ! empty( $term->name ) ) {
            return trim( (string) $term->name );
        }

        $term = get_term_by( 'name', $decoded, $taxonomy );
        if ( $term && ! is_wp_error( $term ) && ! empty( $term->name ) ) {
            return trim( (string) $term->name );
        }
    }

    if ( false === strpos( $decoded, ' ' ) && ( false !== strpos( $decoded, '-' ) || false !== strpos( $decoded, '_' ) ) ) {
        return ucwords( str_replace( [ '-', '_' ], ' ', $decoded ) );
    }

    return $decoded;
}

/**
 * Build dashboard rows for digital order items.
 *
 * @param int $user_id User ID.
 * @param int $limit   Rows limit.
 *
 * @return array<int,array<string,mixed>>
 */
function bw_mew_get_dashboard_digital_orders( $user_id, $limit = 6 ) {
    if ( ! function_exists( 'wc_get_orders' ) || ! function_exists( 'wc_get_customer_available_downloads' ) || ! $user_id ) {
        return [];
    }

    $downloads = wc_get_customer_available_downloads( $user_id );
    $download_map = [];
    foreach ( $downloads as $download ) {
        $order_id   = isset( $download['order_id'] ) ? (int) $download['order_id'] : 0;
        $product_id = isset( $download['product_id'] ) ? (int) $download['product_id'] : 0;
        $download_id = isset( $download['download_id'] ) ? (string) $download['download_id'] : '';
        $url        = isset( $download['download_url'] ) ? (string) $download['download_url'] : '';
        if ( $order_id > 0 && $product_id > 0 && $url ) {
            $download_map[ $order_id . ':' . $product_id ] = esc_url_raw( $url );
        }
        if ( $order_id > 0 && $download_id && $url ) {
            $download_map[ $order_id . ':download:' . $download_id ] = esc_url_raw( $url );
        }
    }

    $orders = wc_get_orders(
        [
            'limit'    => -1,
            'customer' => $user_id,
            'orderby'  => 'date',
            'order'    => 'DESC',
            'status'   => apply_filters( 'woocommerce_my_account_my_orders_query_statuses', [ 'wc-completed', 'wc-processing', 'wc-on-hold' ] ),
            'return'   => 'objects',
        ]
    );

    $rows = [];
    foreach ( $orders as $order ) {
        if ( ! $order instanceof WC_Order ) {
            continue;
        }

        $order_date = $order->get_date_created();
        $date_label = $order_date ? date_i18n( 'F j, Y', $order_date->getTimestamp() ) : '';
        $order_url  = $order->get_view_order_url();

        foreach ( $order->get_items( 'line_item' ) as $item ) {
            if ( ! $item instanceof WC_Order_Item_Product ) {
                continue;
            }

            $product = $item->get_product();
            if ( ! $product instanceof WC_Product || ! $product->is_downloadable() ) {
                continue;
            }

            $product_id    = (int) $item->get_product_id();
            $variation_id  = (int) $item->get_variation_id();
            $order_id      = (int) $order->get_id();
            $download_id   = (string) $item->get_meta( '_download_id', true );
            $download_url  = '';

            $map_keys = [
                $order_id . ':' . $variation_id,
                $order_id . ':' . $product_id,
            ];
            if ( '' !== $download_id ) {
                $map_keys[] = $order_id . ':download:' . $download_id;
            }

            foreach ( $map_keys as $key ) {
                if ( isset( $download_map[ $key ] ) && '' !== $download_map[ $key ] ) {
                    $download_url = $download_map[ $key ];
                    break;
                }
            }

            if ( '' === $download_url ) {
                $order_downloads = $order->get_downloadable_items();
                foreach ( $order_downloads as $order_download ) {
                    $od_product_id   = isset( $order_download['product_id'] ) ? (int) $order_download['product_id'] : 0;
                    $od_download_id  = isset( $order_download['download_id'] ) ? (string) $order_download['download_id'] : '';
                    $od_download_url = isset( $order_download['download_url'] ) ? (string) $order_download['download_url'] : '';
                    if ( $od_product_id === $product_id && '' !== $od_download_url ) {
                        $download_url = esc_url_raw( $od_download_url );
                        break;
                    }
                    if ( '' !== $download_id && $od_download_id === $download_id && '' !== $od_download_url ) {
                        $download_url = esc_url_raw( $od_download_url );
                        break;
                    }
                }
            }
            $thumbnail_url = get_the_post_thumbnail_url( $product_id, 'thumbnail' );
            $rows[] = [
                'title'       => $item->get_name(),
                'license'     => bw_mew_get_order_item_license_label( $item, $product ),
                'date'        => $date_label,
                'price'       => wc_price( (float) $item->get_total() + (float) $item->get_total_tax(), [ 'currency' => $order->get_currency() ] ),
                'thumbnail'   => $thumbnail_url ? esc_url_raw( $thumbnail_url ) : '',
                'downloadUrl' => $download_url,
                'orderUrl'    => $order_url,
            ];

            if ( count( $rows ) >= absint( $limit ) ) {
                return $rows;
            }
        }
    }

    return $rows;
}

/**
 * Build dashboard rows for physical order items.
 *
 * @param int $user_id User ID.
 * @param int $limit   Rows limit.
 *
 * @return array<int,array<string,mixed>>
 */
function bw_mew_get_dashboard_physical_orders( $user_id, $limit = 6 ) {
    if ( ! function_exists( 'wc_get_orders' ) || ! $user_id ) {
        return [];
    }

    $orders = wc_get_orders(
        [
            'limit'    => -1,
            'customer' => $user_id,
            'orderby'  => 'date',
            'order'    => 'DESC',
            'status'   => apply_filters( 'woocommerce_my_account_my_orders_query_statuses', [ 'wc-completed', 'wc-processing', 'wc-on-hold' ] ),
            'return'   => 'objects',
        ]
    );

    $rows = [];
    foreach ( $orders as $order ) {
        if ( ! $order instanceof WC_Order ) {
            continue;
        }

        $order_date = $order->get_date_created();
        $date_label = $order_date ? date_i18n( 'F j, Y', $order_date->getTimestamp() ) : '';
        $order_url  = $order->get_view_order_url();

        foreach ( $order->get_items( 'line_item' ) as $item ) {
            if ( ! $item instanceof WC_Order_Item_Product ) {
                continue;
            }

            $product = $item->get_product();
            if ( ! $product instanceof WC_Product || $product->is_downloadable() ) {
                continue;
            }

            $product_id    = (int) $item->get_product_id();
            $thumbnail_url = get_the_post_thumbnail_url( $product_id, 'thumbnail' );
            $rows[] = [
                'title'     => $item->get_name(),
                'date'      => $date_label,
                'price'     => wc_price( (float) $item->get_total() + (float) $item->get_total_tax(), [ 'currency' => $order->get_currency() ] ),
                'thumbnail' => $thumbnail_url ? esc_url_raw( $thumbnail_url ) : '',
                'orderUrl'  => $order_url,
            ];

            if ( count( $rows ) >= absint( $limit ) ) {
                return $rows;
            }
        }
    }

    return $rows;
}

/**
 * Get recent orders for the current customer.
 *
 * @param int $limit Number of orders to return.
 *
 * @return array
 */
function bw_mew_get_recent_customer_orders( $limit = 3 ) {
    if ( ! is_user_logged_in() ) {
        return [];
    }

    $args = [
        'limit'        => absint( $limit ),
        'customer'     => get_current_user_id(),
        'orderby'      => 'date',
        'order'        => 'DESC',
        'status'       => apply_filters( 'woocommerce_my_account_my_orders_query_statuses', [ 'wc-completed', 'wc-processing', 'wc-on-hold' ] ),
    ];

    return wc_get_orders( $args );
}

/**
 * Retrieve available coupons for the current customer.
 *
 * @return array
 */
function bw_mew_get_customer_coupons() {
    if ( ! is_user_logged_in() ) {
        return [];
    }

    $customer_email = wp_get_current_user()->user_email;

    // Smart Coupons compatibility.
    if ( function_exists( 'wc_sc_get_available_coupons' ) ) {
        return wc_sc_get_available_coupons( $customer_email );
    }

    $coupons = wc_get_coupons(
        [
            'orderby' => 'date',
            'order'   => 'DESC',
            'limit'   => -1,
            'return'  => 'objects',
        ]
    );

    $available = [];

    foreach ( $coupons as $coupon ) {
        if ( ! $coupon instanceof WC_Coupon ) {
            continue;
        }

        $email_restrictions = array_map( 'strtolower', (array) $coupon->get_email_restrictions() );
        if ( ! empty( $email_restrictions ) && ! in_array( strtolower( $customer_email ), $email_restrictions, true ) ) {
            continue;
        }

        $available[] = $coupon;
    }

    return $available;
}

/**
 * Output password gating modal HTML on My Account pages.
 *
 * Only outputs when provider is Supabase and user is logged in.
 */
function bw_mew_output_password_gating_modal() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
        return;
    }

    if ( ! is_user_logged_in() ) {
        return;
    }

    $provider = get_option( 'bw_account_login_provider', 'wordpress' );
    if ( 'supabase' !== $provider ) {
        return;
    }

    ?>
    <div id="bw-password-modal" class="bw-password-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="bw-password-modal-title">
        <div class="bw-password-modal__overlay"></div>
        <div class="bw-password-modal__container">
            <div class="bw-password-modal__content">
                <h2 id="bw-password-modal-title" class="bw-password-modal__title"><?php esc_html_e( 'Set Password', 'bw' ); ?></h2>
                <p class="bw-password-modal__subtitle"><?php esc_html_e( 'For better security and to complete your account, please choose a password.', 'bw' ); ?></p>

                <form id="bw-password-modal-form" class="bw-password-modal__form" autocomplete="off">
                    <div class="bw-password-modal__field">
                        <label for="bw_modal_new_password"><?php esc_html_e( 'New password', 'bw' ); ?></label>
                        <div class="bw-password-modal__input-wrap">
                            <input type="password" id="bw_modal_new_password" name="new_password" autocomplete="new-password" required minlength="8" />
                            <button type="button" class="bw-password-modal__toggle" data-target="bw_modal_new_password" aria-label="<?php esc_attr_e( 'Show password', 'bw' ); ?>">
                                <svg class="bw-icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="bw-icon-eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="bw-password-modal__field">
                        <label for="bw_modal_confirm_password"><?php esc_html_e( 'Confirm password', 'bw' ); ?></label>
                        <div class="bw-password-modal__input-wrap">
                            <input type="password" id="bw_modal_confirm_password" name="confirm_password" autocomplete="new-password" required minlength="8" />
                            <button type="button" class="bw-password-modal__toggle" data-target="bw_modal_confirm_password" aria-label="<?php esc_attr_e( 'Show password', 'bw' ); ?>">
                                <svg class="bw-icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="bw-icon-eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <ul class="bw-password-modal__rules">
                        <li data-rule="length"><?php esc_html_e( 'At least 8 characters', 'bw' ); ?></li>
                        <li data-rule="upper"><?php esc_html_e( 'At least 1 uppercase letter', 'bw' ); ?></li>
                        <li data-rule="number"><?php esc_html_e( 'At least 1 number or special character', 'bw' ); ?></li>
                    </ul>

                    <div class="bw-password-modal__error" role="alert" aria-live="polite" hidden></div>

                    <button type="submit" class="bw-password-modal__submit"><?php esc_html_e( 'Save password', 'bw' ); ?></button>
                </form>
            </div>
        </div>
    </div>
    <?php
}
add_action( 'wp_footer', 'bw_mew_output_password_gating_modal', 50 );

/**
 * Enqueue password gating modal assets on My Account pages.
 */
function bw_mew_enqueue_password_modal_assets() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
        return;
    }

    if ( ! is_user_logged_in() ) {
        return;
    }

    $provider = get_option( 'bw_account_login_provider', 'wordpress' );
    if ( 'supabase' !== $provider ) {
        return;
    }

    $css_file = BW_MEW_PATH . 'assets/css/bw-password-modal.css';
    $css_ver  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';
    $js_file  = BW_MEW_PATH . 'assets/js/bw-password-modal.js';
    $js_ver   = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

    wp_enqueue_style(
        'bw-password-modal',
        BW_MEW_URL . 'assets/css/bw-password-modal.css',
        [],
        $css_ver
    );

    wp_enqueue_script(
        'bw-password-modal',
        BW_MEW_URL . 'assets/js/bw-password-modal.js',
        [],
        $js_ver,
        true
    );

    wp_localize_script(
        'bw-password-modal',
        'bwPasswordModal',
        [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'bw-supabase-login' ),
            'logoutUrl' => wp_logout_url( add_query_arg( 'logged_out', '1', wc_get_page_permalink( 'myaccount' ) ) ),
            'accountUrl' => wc_get_page_permalink( 'myaccount' ),
            'i18n'    => [
                'saving'           => __( 'Saving...', 'bw' ),
                'savePassword'     => __( 'Save password', 'bw' ),
                'passwordMismatch' => __( 'Passwords do not match.', 'bw' ),
                'passwordTooShort' => __( 'Password must be at least 8 characters.', 'bw' ),
                'genericError'     => __( 'Unable to save password. Please try again.', 'bw' ),
                'sessionMissingPrefix' => __( 'Supabase session is missing. Please log in again ', 'bw' ),
                'sessionMissingLink'   => __( 'here', 'bw' ),
            ],
        ]
    );
}
add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_password_modal_assets', 30 );
