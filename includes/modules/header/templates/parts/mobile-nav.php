<?php
if (!defined('ABSPATH')) {
    exit;
}

$privacy_policy_url = function_exists('get_privacy_policy_url') ? get_privacy_policy_url() : '';
$terms_policy_url = '';
$cookie_policy_url = '';
$wc_terms_page_id = (int) get_option('woocommerce_terms_page_id', 0);

if ($wc_terms_page_id > 0) {
    $terms_policy_url = get_permalink($wc_terms_page_id);
}

if (empty($terms_policy_url)) {
    $terms_page = get_page_by_path('terms-of-service');
    if ($terms_page instanceof WP_Post) {
        $terms_policy_url = get_permalink($terms_page);
    }
}

if (empty($terms_policy_url)) {
    $terms_policy_url = home_url('/terms-of-service/');
}

if (empty($cookie_policy_url)) {
    $cookie_candidates = ['cookie-policy', 'cookies', 'cookie-notice'];
    foreach ($cookie_candidates as $cookie_slug) {
        $cookie_page = get_page_by_path($cookie_slug);
        if ($cookie_page instanceof WP_Post) {
            $cookie_policy_url = get_permalink($cookie_page);
            break;
        }
    }
}

$account_url = function_exists('wc_get_page_permalink')
    ? wc_get_page_permalink('myaccount')
    : home_url('/my-account/');

if (empty($account_url)) {
    $account_url = home_url('/my-account/');
}

$current_user = is_user_logged_in() ? wp_get_current_user() : null;
$is_logged_in = $current_user instanceof WP_User && $current_user->exists();
$user_display_name = '';
$user_email = '';

if ($is_logged_in) {
    $user_display_name = trim((string) $current_user->display_name);
    $user_email = sanitize_email((string) $current_user->user_email);

    if ($user_display_name === '' && $user_email !== '') {
        $user_display_name = $user_email;
    }

    if ($user_display_name === '') {
        $user_display_name = __('My account', 'bw');
    }
}

$profile_title = $is_logged_in ? $user_display_name : __('Login or Join', 'bw');
$profile_subtitle = $is_logged_in
    ? ($user_email !== '' ? $user_email : __('Member profile', 'bw'))
    : __('Access your account', 'bw');
$profile_cta_label = $is_logged_in ? __('My Account', 'bw') : __('Login or Join', 'bw');
$logout_url = $is_logged_in ? wp_logout_url(add_query_arg('logged_out', '1', $account_url)) : '';
$avatar_html = $is_logged_in ? get_avatar($current_user->ID, 96, '', $user_display_name, ['class' => 'bw-navigation__profile-avatar-image']) : '';
?>
<div class="bw-navigation__mobile-overlay" aria-hidden="true">
    <div
        class="bw-navigation__mobile-panel"
        role="dialog"
        aria-modal="true"
        aria-label="<?php esc_attr_e('Mobile menu', 'bw'); ?>"
        tabindex="-1"
        data-bw-navigation-panel
    >
        <div class="bw-navigation__mobile-header">
            <button class="bw-navigation__close" type="button" aria-label="<?php esc_attr_e('Close menu', 'bw'); ?>">
                <span class="bw-navigation__close-icon" aria-hidden="true"></span>
            </button>
        </div>

        <div class="bw-navigation__mobile-content">
            <section class="bw-navigation__mobile-section bw-navigation__mobile-section--navigation" aria-label="<?php esc_attr_e('Mobile navigation', 'bw'); ?>">
                <nav class="bw-navigation__mobile" aria-label="<?php esc_attr_e('Mobile navigation', 'bw'); ?>">
                    <?php echo $mobile_menu_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </nav>
            </section>

            <?php include BW_MEW_PATH . 'includes/modules/header/templates/parts/mobile-nav-profile.php'; ?>
            <?php include BW_MEW_PATH . 'includes/modules/header/templates/parts/mobile-nav-auth.php'; ?>
        </div>

        <?php include BW_MEW_PATH . 'includes/modules/header/templates/parts/mobile-nav-footer.php'; ?>
    </div>
</div>
