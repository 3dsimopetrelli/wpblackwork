<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="bw-navigation__mobile-section bw-navigation__mobile-section--profile" aria-label="<?php esc_attr_e('Account', 'bw'); ?>">
    <?php echo function_exists('bw_header_render_account_module') ? bw_header_render_account_module() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>
