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
    </div>
</div>
