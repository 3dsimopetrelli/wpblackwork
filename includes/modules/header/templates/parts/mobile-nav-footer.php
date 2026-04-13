<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="bw-navigation__mobile-section bw-navigation__mobile-section--footer" aria-label="<?php esc_attr_e('Legal and social', 'bw'); ?>">
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
                <?php if (!empty($cookie_policy_url)) : ?>
                    <a class="bw-navigation__mobile-footer-link" href="<?php echo esc_url($cookie_policy_url); ?>">
                        <?php esc_html_e('Cookies', 'bw'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <button class="bw-navigation__mobile-footer-social" type="button" aria-label="<?php esc_attr_e('Instagram', 'bw'); ?>">
                <span class="bw-navigation__mobile-footer-social-icon" aria-hidden="true"></span>
            </button>
        </div>
    </div>
</section>
