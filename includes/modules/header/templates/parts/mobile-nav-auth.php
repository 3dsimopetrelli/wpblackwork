<?php
if (!defined('ABSPATH')) {
    exit;
}

if (empty($is_logged_in) || empty($logout_url)) {
    return;
}
?>
<section class="bw-navigation__mobile-section bw-navigation__mobile-section--auth" aria-label="<?php esc_attr_e('Account actions', 'bw'); ?>">
    <a class="bw-navigation__mobile-auth-link" href="<?php echo esc_url($logout_url); ?>">
        <span class="bw-navigation__mobile-auth-link-label"><?php esc_html_e('Logout', 'bw'); ?></span>
        <span class="bw-navigation__mobile-auth-link-icon" aria-hidden="true"></span>
    </a>
</section>
