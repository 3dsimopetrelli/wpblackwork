<?php
if (!defined('ABSPATH')) {
    exit;
}

$is_guest = empty($is_logged_in);
?>
<section class="bw-navigation__mobile-section bw-navigation__mobile-section--profile" aria-label="<?php esc_attr_e('Account', 'bw'); ?>">
    <?php if ($is_guest) : ?>
        <a class="bw-navigation__mobile-cta bw-navigation__mobile-guest-cta" href="<?php echo esc_url($account_url); ?>">
            <?php esc_html_e('Login or Join', 'bw'); ?>
        </a>
    <?php else : ?>
        <div class="bw-navigation__profile-card is-logged-in">
            <div class="bw-navigation__profile-avatar" aria-hidden="true">
                <?php if (!empty($avatar_html)) : ?>
                    <?php echo $avatar_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php else : ?>
                    <span class="bw-navigation__profile-avatar-placeholder">BW</span>
                <?php endif; ?>
            </div>

            <div class="bw-navigation__profile-body">
                <p class="bw-navigation__profile-name"><?php echo esc_html($profile_title); ?></p>
                <p class="bw-navigation__profile-email"><?php echo esc_html($profile_subtitle); ?></p>
                <div class="bw-navigation__profile-actions" aria-label="<?php esc_attr_e('Account actions', 'bw'); ?>">
                    <a class="bw-navigation__mobile-cta bw-navigation__profile-cta bw-navigation__profile-account-cta" href="<?php echo esc_url($account_url); ?>">
                        <?php echo esc_html($profile_cta_label); ?>
                    </a>
                    <?php if (!empty($logout_url)) : ?>
                        <a class="bw-navigation__mobile-auth-link bw-navigation__profile-logout-cta" href="<?php echo esc_url($logout_url); ?>">
                            <?php esc_html_e('Logout', 'bw'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>
