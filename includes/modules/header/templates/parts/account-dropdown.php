<?php
if (!defined('ABSPATH')) {
    exit;
}

$account_context = function_exists('bw_header_get_account_context') ? bw_header_get_account_context() : [];
$popup_id = 'bw-account-dropdown-panel';
$breakpoint = function_exists('bw_header_get_mobile_breakpoint') ? bw_header_get_mobile_breakpoint() : 1024;
?>
<div class="bw-navshop__account-dropdown bw-navigation__account-dropdown" data-bw-account-dropdown data-bw-account-dropdown-breakpoint="<?php echo esc_attr((string) $breakpoint); ?>">
    <a
        href="<?php echo esc_url(isset($account_context['account_url']) ? $account_context['account_url'] : home_url('/my-account/')); ?>"
        class="bw-navshop__item bw-navshop__account bw-navshop__account-trigger"
        aria-haspopup="dialog"
        aria-expanded="false"
        aria-controls="<?php echo esc_attr($popup_id); ?>"
        data-bw-account-dropdown-trigger
    >
        <?php echo esc_html($account_label); ?>
    </a>
    <div
        class="bw-navigation__popup-panel bw-navigation__account-dropdown-panel bw-navigation__account-dropdown-surface bw-surface-glass"
        id="<?php echo esc_attr($popup_id); ?>"
        role="dialog"
        aria-modal="false"
        aria-hidden="true"
        aria-label="<?php esc_attr_e('Account', 'bw'); ?>"
        tabindex="-1"
        data-bw-account-dropdown-panel
    >
        <div class="bw-navigation__mobile-content bw-navigation__account-dropdown-content">
            <?php echo function_exists('bw_header_render_account_module') ? bw_header_render_account_module($account_context) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </div>
</div>
