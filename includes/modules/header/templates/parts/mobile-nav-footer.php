<?php
if (!defined('ABSPATH')) {
    exit;
}

$footer_menu_html = function_exists('bw_header_render_footer_menu')
    ? bw_header_render_footer_menu('bw_mobile_footer_menu', 'bw-navigation__mobile-footer-menu')
    : '';
?>
<section class="bw-navigation__mobile-section bw-navigation__mobile-section--footer" aria-label="<?php esc_attr_e('Legal and social', 'bw'); ?>">
    <div class="bw-navigation__mobile-footer">
        <div class="bw-navigation__mobile-footer-divider" aria-hidden="true"></div>
        <div class="bw-navigation__mobile-footer-row">
            <div class="bw-navigation__mobile-footer-links">
                <?php if (!empty($footer_menu_html)) : ?>
                    <?php echo $footer_menu_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php else : ?>
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
                <?php endif; ?>
            </div>
            <span class="bw-navigation__mobile-footer-social" aria-hidden="true">
                <span class="bw-navigation__mobile-footer-social-icon" aria-hidden="true"></span>
            </span>
        </div>
    </div>
</section>
