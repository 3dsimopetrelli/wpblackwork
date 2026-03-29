<?php
if (!defined('ABSPATH')) {
    exit;
}

$privacy_policy_url = function_exists('get_privacy_policy_url') ? get_privacy_policy_url() : '';
$terms_policy_url = '';
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
?>
<div class="bw-navigation__mobile-overlay" aria-hidden="true">
    <div class="bw-navigation__mobile-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Mobile menu', 'bw'); ?>">
        <div class="bw-navigation__mobile-header">
            <button class="bw-navigation__close" type="button" aria-label="<?php esc_attr_e('Close menu', 'bw'); ?>">
                <span class="bw-navigation__close-icon" aria-hidden="true"></span>
            </button>
        </div>

        <div class="bw-navigation__mobile-content">
            <nav class="bw-navigation__mobile" aria-label="<?php esc_attr_e('Mobile navigation', 'bw'); ?>">
                <?php echo $mobile_menu_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </nav>

            <?php
            $is_logged_in = is_user_logged_in();
            $auth_label = $is_logged_in ? __('My Account', 'bw') : __('Login', 'bw');
            $auth_url = $is_logged_in
                ? (isset($account_link) && !empty($account_link) ? $account_link : home_url('/my-account/'))
                : (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_registration_url());
            ?>

            <div class="bw-navigation__mobile-divider" aria-hidden="true"></div>
            <a class="bw-navigation__link bw-navigation__auth-link" href="<?php echo esc_url($auth_url); ?>">
                <?php echo esc_html($auth_label); ?>
            </a>

            <div class="bw-navigation__mobile-footer">
                <div class="bw-navigation__mobile-footer-divider" aria-hidden="true"></div>
                <div class="bw-navigation__mobile-footer-row">
                    <div class="bw-navigation__mobile-footer-links">
                        <?php if (!empty($privacy_policy_url)) : ?>
                            <a class="bw-navigation__mobile-footer-link" href="<?php echo esc_url($privacy_policy_url); ?>">
                                <?php esc_html_e('Privacy Policy', 'bw'); ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($terms_policy_url)) : ?>
                            <a class="bw-navigation__mobile-footer-link" href="<?php echo esc_url($terms_policy_url); ?>">
                                <?php esc_html_e('Terms Policy', 'bw'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <button class="bw-navigation__mobile-footer-social" type="button" aria-label="<?php esc_attr_e('Instagram', 'bw'); ?>">
                        <span class="bw-navigation__mobile-footer-social-icon" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
