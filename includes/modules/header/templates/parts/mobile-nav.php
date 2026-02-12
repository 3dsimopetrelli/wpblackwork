<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="bw-navigation__mobile-overlay" aria-hidden="true">
    <div class="bw-navigation__mobile-panel">
        <button class="bw-navigation__close" type="button" aria-label="<?php esc_attr_e('Close menu', 'bw'); ?>">
            <span class="bw-navigation__close-icon" aria-hidden="true"></span>
        </button>

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
    </div>
</div>
