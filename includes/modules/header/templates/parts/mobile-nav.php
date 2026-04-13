<?php
if (!defined('ABSPATH')) {
    exit;
}

$privacy_policy_url = function_exists('get_privacy_policy_url') ? get_privacy_policy_url() : '';
$terms_policy_url = '';
$cookie_policy_url = '';
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

if (empty($cookie_policy_url)) {
    $cookie_candidates = ['cookie-policy', 'cookies', 'cookie-notice'];
    foreach ($cookie_candidates as $cookie_slug) {
        $cookie_page = get_page_by_path($cookie_slug);
        if ($cookie_page instanceof WP_Post) {
            $cookie_policy_url = get_permalink($cookie_page);
            break;
        }
    }
}
?>
<div class="bw-navigation__mobile-overlay" aria-hidden="true">
    <div
        class="bw-navigation__mobile-panel bw-navigation__popup-panel bw-navigation__mobile-popup-surface"
        role="dialog"
        aria-modal="true"
        aria-label="<?php esc_attr_e('Mobile menu', 'bw'); ?>"
        tabindex="-1"
        data-bw-navigation-panel
    >
        <div class="bw-navigation__mobile-content">
            <section class="bw-navigation__mobile-section bw-navigation__mobile-section--navigation" aria-label="<?php esc_attr_e('Mobile navigation', 'bw'); ?>">
                <nav class="bw-navigation__mobile" aria-label="<?php esc_attr_e('Mobile navigation', 'bw'); ?>">
                    <?php echo $mobile_menu_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </nav>
            </section>

            <?php include BW_MEW_PATH . 'includes/modules/header/templates/parts/mobile-nav-profile.php'; ?>
        </div>

        <?php include BW_MEW_PATH . 'includes/modules/header/templates/parts/mobile-nav-footer.php'; ?>
    </div>
</div>
