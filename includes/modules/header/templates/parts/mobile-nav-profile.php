<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="bw-navigation__mobile-section bw-navigation__mobile-section--profile" aria-label="<?php esc_attr_e('Account', 'bw'); ?>">
    <div class="bw-navigation__profile-card<?php echo !empty($is_logged_in) ? ' is-logged-in' : ' is-guest'; ?>">
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
            <a class="bw-navigation__mobile-cta bw-navigation__profile-cta" href="<?php echo esc_url($account_url); ?>">
                <?php echo esc_html($profile_cta_label); ?>
            </a>
        </div>
    </div>
</section>
