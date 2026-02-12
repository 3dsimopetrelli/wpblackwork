<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<noscript>
    <style>
        .bw-custom-header.bw-header-preload {
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }
        .bw-custom-header.bw-header-preload .bw-custom-header__inner {
            transform: none !important;
        }
    </style>
</noscript>
<header
    class="<?php echo esc_attr(trim((isset($header_classes) ? $header_classes : 'bw-custom-header') . ' bw-header-preload')); ?>"
    role="banner"
    aria-label="<?php echo esc_attr($header_title); ?>"
    data-smart-scroll="<?php echo !empty($smart_scroll_enabled) ? 'yes' : 'no'; ?>"
>
    <div class="bw-custom-header__inner">
        <div class="bw-custom-header__desktop">
            <div class="bw-custom-header__desktop-panel<?php echo !empty($menu_blur_enabled) ? ' is-blur-enabled' : ''; ?>">
                <div class="bw-custom-header__desktop-logo">
                    <a class="bw-custom-header__logo-link" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('Home', 'bw'); ?>">
                        <?php echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </a>
                </div>
                <div class="bw-custom-header__desktop-center">
                    <nav class="bw-navigation__desktop bw-custom-header__desktop-menu" aria-label="<?php esc_attr_e('Desktop navigation', 'bw'); ?>">
                        <?php echo $desktop_menu_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </nav>
                    <div class="bw-custom-header__desktop-search">
                        <?php echo $search_desktop_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                </div>

                <div class="bw-custom-header__desktop-right bw-header-navshop">
                    <div class="bw-navshop bw-navshop--hide-account-mobile">
                        <a href="<?php echo esc_url($account_link); ?>" class="bw-navshop__item bw-navshop__account"><?php echo esc_html($account_label); ?></a>
                        <a href="<?php echo esc_url($cart_link); ?>" class="bw-navshop__item bw-navshop__cart" aria-label="<?php echo esc_attr($cart_label); ?>" data-use-popup="yes">
                            <span class="bw-navshop__cart-label"><?php echo esc_html($cart_label); ?></span>
                            <span class="bw-navshop__cart-icon" aria-hidden="true"><?php echo $cart_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <span class="<?php echo esc_attr($cart_count_class); ?>"><?php echo esc_html($cart_count); ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="bw-custom-header__mobile">
            <div class="bw-custom-header__mobile-left bw-header-navigation">
                <div class="bw-navigation">
                    <button class="bw-navigation__toggle" type="button" aria-expanded="false" aria-label="<?php esc_attr_e('Open menu', 'bw'); ?>">
                        <span class="bw-navigation__toggle-icon" aria-hidden="true"><?php echo $hamburger_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    </button>
                    <?php include BW_MEW_PATH . 'includes/modules/header/templates/parts/mobile-nav.php'; ?>
                </div>
            </div>

            <div class="bw-custom-header__mobile-center">
                <a class="bw-custom-header__logo-link" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('Home', 'bw'); ?>">
                    <?php echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </a>
            </div>

            <div class="bw-custom-header__mobile-right">
                <?php echo $search_mobile_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <div class="bw-header-navshop bw-header-navshop--mobile">
                    <div class="bw-navshop bw-navshop--hide-account-mobile">
                        <a href="<?php echo esc_url($account_link); ?>" class="bw-navshop__item bw-navshop__account"><?php echo esc_html($account_label); ?></a>
                        <a href="<?php echo esc_url($cart_link); ?>" class="bw-navshop__item bw-navshop__cart" aria-label="<?php echo esc_attr($cart_label); ?>" data-use-popup="yes">
                            <span class="bw-navshop__cart-label"><?php echo esc_html($cart_label); ?></span>
                            <span class="bw-navshop__cart-icon" aria-hidden="true"><?php echo $cart_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <span class="<?php echo esc_attr($cart_count_class); ?>"><?php echo esc_html($cart_count); ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
