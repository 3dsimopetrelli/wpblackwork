<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings = function_exists('bw_link_page_get_settings') ? bw_link_page_get_settings() : [];
$links = isset($settings['links']) && is_array($settings['links']) ? $settings['links'] : [];
$title = isset($settings['title']) ? (string) $settings['title'] : '';
$description = isset($settings['description']) ? (string) $settings['description'] : '';
$newsletter_enabled = !empty($settings['newsletter_enabled']);
$newsletter_show_name = !empty($settings['newsletter_show_name']);
$newsletter_email_placeholder = isset($settings['newsletter_email_placeholder']) && '' !== trim((string) $settings['newsletter_email_placeholder'])
    ? (string) $settings['newsletter_email_placeholder']
    : 'Your email';
$newsletter_name_placeholder = isset($settings['newsletter_name_placeholder']) && '' !== trim((string) $settings['newsletter_name_placeholder'])
    ? (string) $settings['newsletter_name_placeholder']
    : 'Your name';
$newsletter_button_label = isset($settings['newsletter_button_label']) && '' !== trim((string) $settings['newsletter_button_label'])
    ? (string) $settings['newsletter_button_label']
    : 'Subscribe';
$newsletter_helper_text = isset($settings['newsletter_helper_text']) ? (string) $settings['newsletter_helper_text'] : '';
$newsletter_image_id = isset($settings['newsletter_image_id']) ? (int) $settings['newsletter_image_id'] : 0;
$newsletter_image_url = $newsletter_image_id > 0 ? wp_get_attachment_image_url($newsletter_image_id, 'large') : '';
$logo_id = isset($settings['logo_id']) ? (int) $settings['logo_id'] : 0;
$logo_url = $logo_id > 0 ? wp_get_attachment_image_url($logo_id, 'full') : '';
$page_id = isset($settings['page_id']) ? (int) $settings['page_id'] : 0;
$background_color = isset($settings['background_color']) ? sanitize_hex_color((string) $settings['background_color']) : '#0f0f0f';
$background_color = $background_color ? $background_color : '#0f0f0f';
$background_image_id = isset($settings['background_image_id']) ? (int) $settings['background_image_id'] : 0;
$background_image_url = $background_image_id > 0 ? wp_get_attachment_image_url($background_image_id, 'full') : '';
$logo_width = isset($settings['logo_width']) ? absint($settings['logo_width']) : 180;
$logo_width = max(40, min(600, $logo_width));
$logo_rotate_enabled = !empty($settings['logo_rotate']);
$logo_rotate_speed = isset($settings['logo_rotate_speed']) && is_numeric($settings['logo_rotate_speed']) ? (float) $settings['logo_rotate_speed'] : 18.0;
$logo_rotate_speed = max(2.0, min(120.0, $logo_rotate_speed));

$social_links = isset($settings['social_links']) && is_array($settings['social_links']) ? $settings['social_links'] : [];

$has_socials = false;
foreach ($social_links as $social_link) {
    if (!empty($social_link['label']) && !empty($social_link['url'])) {
        $has_socials = true;
        break;
    }
}

$css_path = plugin_dir_path(__FILE__) . '../assets/link-page.css';
$css_url = plugin_dir_url(__FILE__) . '../assets/link-page.css';
$js_path = plugin_dir_path(__FILE__) . '../assets/link-page.js';
$js_url = plugin_dir_url(__FILE__) . '../assets/link-page.js';

$render_links = [];
foreach ($links as $index => $link) {
    $label = isset($link['label']) ? (string) $link['label'] : '';
    $url = isset($link['url']) ? (string) $link['url'] : '';
    if ('' === $label || '' === $url) {
        continue;
    }

    $target = !empty($link['target']) ? '_blank' : '_self';
    $rel = '_blank' === $target ? 'noopener noreferrer' : '';
    $link_id = function_exists('bw_link_page_build_link_id') ? bw_link_page_build_link_id($link, $index) : ('link-' . (string) $index);
    $button_color = isset($link['button_color']) ? sanitize_hex_color((string) $link['button_color']) : '';
    $border_color = isset($link['border_color']) ? sanitize_hex_color((string) $link['border_color']) : '';
    $link_style_parts = [];
    if (!empty($button_color)) {
        $link_style_parts[] = '--bw-link-button-bg:' . $button_color;
    }
    if (!empty($border_color)) {
        $link_style_parts[] = '--bw-link-button-border:' . $border_color;
    }

    $render_links[] = [
        'label' => $label,
        'url' => $url,
        'target' => $target,
        'rel' => $rel,
        'link_id' => $link_id,
        'link_style' => implode(';', $link_style_parts),
    ];
}

$should_load_tracking_js = ($page_id > 0 && !empty($render_links));
$should_load_newsletter_js = $newsletter_enabled;

$newsletter_consent_required = true;
$newsletter_consent_prefix = __('I agree to the', 'bw');
$newsletter_privacy_link_label = __('Privacy Policy', 'bw');
$newsletter_privacy_url = function_exists('get_privacy_policy_url') ? get_privacy_policy_url() : '';

if (class_exists('BW_Mail_Marketing_Settings')) {
    $subscription_settings = BW_Mail_Marketing_Settings::get_subscription_settings();
    $newsletter_consent_required = !isset($subscription_settings['consent_required']) || !empty($subscription_settings['consent_required']);
    if (!empty($subscription_settings['consent_prefix'])) {
        $newsletter_consent_prefix = (string) $subscription_settings['consent_prefix'];
    }
    if (!empty($subscription_settings['privacy_link_label'])) {
        $newsletter_privacy_link_label = (string) $subscription_settings['privacy_link_label'];
    }
    if (!empty($subscription_settings['privacy_url'])) {
        $newsletter_privacy_url = (string) $subscription_settings['privacy_url'];
    }
}

$frontend_config = [
    'analytics' => [
        'enabled' => $should_load_tracking_js,
        'endpoint' => admin_url('admin-ajax.php'),
        'action' => 'bw_link_page_track_click',
        'nonce' => wp_create_nonce('bw_link_page_track_click'),
        'pageId' => $page_id,
    ],
    'newsletter' => [
        'enabled' => $should_load_newsletter_js,
        'endpoint' => admin_url('admin-ajax.php'),
        'action' => 'bw_mail_marketing_subscribe',
        'nonce' => wp_create_nonce('bw_mail_marketing_subscription_submit'),
        'consentRequired' => $newsletter_consent_required ? 1 : 0,
    ],
];

$body_classes = [];
if ($logo_rotate_enabled) {
    $body_classes[] = 'bw-link-page-logo-rotate';
}

$body_style = sprintf(
    '--bw-link-bg:%1$s;--bw-link-logo-width:%2$spx;--bw-link-logo-rotate-duration:%3$ss;',
    $background_color,
    (string) $logo_width,
    rtrim(rtrim(number_format($logo_rotate_speed, 1, '.', ''), '0'), '.')
);

if (!empty($background_image_url)) {
    $body_style .= '--bw-link-bg-image:url(' . esc_url_raw($background_image_url) . ');';
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html(get_the_title()); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url($css_url); ?>?ver=<?php echo esc_attr((string) (file_exists($css_path) ? filemtime($css_path) : '1.0.0')); ?>">
</head>
<body class="<?php echo esc_attr(implode(' ', $body_classes)); ?>" style="<?php echo esc_attr($body_style); ?>">
<div class="wrapper">
    <div class="container" data-bw-page-id="<?php echo esc_attr((string) $page_id); ?>">
        <?php if (!empty($logo_url)) : ?>
            <div class="logo">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
            </div>
        <?php endif; ?>

        <?php if ('' !== $title) : ?>
            <h1 class="title"><?php echo esc_html($title); ?></h1>
        <?php endif; ?>

        <?php if ('' !== $description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>

        <?php if ($newsletter_enabled) : ?>
            <div class="newsletter-block">
                <form class="newsletter-form" method="post" novalidate data-consent-required="<?php echo $newsletter_consent_required ? '1' : '0'; ?>">
                    <?php if ($newsletter_show_name) : ?>
                        <div class="newsletter-field">
                            <input
                                type="text"
                                name="name"
                                autocomplete="name"
                                placeholder="<?php echo esc_attr($newsletter_name_placeholder); ?>"
                                aria-label="<?php echo esc_attr($newsletter_name_placeholder); ?>"
                            >
                        </div>
                    <?php endif; ?>

                    <div class="newsletter-inline">
                        <div class="newsletter-field newsletter-field-email">
                            <input
                                type="email"
                                name="email"
                                autocomplete="email"
                                required
                                placeholder="<?php echo esc_attr($newsletter_email_placeholder); ?>"
                                aria-label="<?php echo esc_attr($newsletter_email_placeholder); ?>"
                            >
                        </div>
                        <button class="newsletter-submit" type="submit"><?php echo esc_html($newsletter_button_label); ?></button>
                    </div>

                    <?php if ($newsletter_consent_required) : ?>
                        <label class="newsletter-consent">
                            <input type="checkbox" name="privacy" value="1" required>
                            <span>
                                <?php echo esc_html($newsletter_consent_prefix); ?>
                                <?php if (!empty($newsletter_privacy_url)) : ?>
                                    <a href="<?php echo esc_url($newsletter_privacy_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($newsletter_privacy_link_label); ?></a>
                                <?php else : ?>
                                    <?php echo ' ' . esc_html($newsletter_privacy_link_label); ?>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endif; ?>

                    <div class="newsletter-message" role="status" aria-live="polite"></div>
                </form>

                <?php if ('' !== $newsletter_helper_text) : ?>
                    <p class="newsletter-helper"><?php echo esc_html($newsletter_helper_text); ?></p>
                <?php endif; ?>

                <?php if (!empty($newsletter_image_url)) : ?>
                    <div class="newsletter-image">
                        <img src="<?php echo esc_url($newsletter_image_url); ?>" alt="">
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="links">
            <?php foreach ($render_links as $render_link) : ?>
                <a class="link-item"
                    href="<?php echo esc_url($render_link['url']); ?>"
                    target="<?php echo esc_attr($render_link['target']); ?>"
                    data-bw-link-id="<?php echo esc_attr($render_link['link_id']); ?>"
                    data-bw-link-label="<?php echo esc_attr($render_link['label']); ?>"
                    <?php echo '' !== $render_link['link_style'] ? ' style="' . esc_attr($render_link['link_style']) . '"' : ''; ?>
                    <?php echo '' !== $render_link['rel'] ? ' rel="' . esc_attr($render_link['rel']) . '"' : ''; ?>>
                    <?php echo esc_html($render_link['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($has_socials) : ?>
            <div class="socials">
                <?php foreach ($social_links as $social_link) :
                    $label = isset($social_link['label']) ? (string) $social_link['label'] : '';
                    $url = isset($social_link['url']) ? (string) $social_link['url'] : '';
                    if ('' === $label || '' === $url) {
                        continue;
                    }
                    $target = !empty($social_link['target']) ? '_blank' : '_self';
                    $rel = '_blank' === $target ? 'noopener noreferrer' : '';
                    ?>
                    <a href="<?php echo esc_url($url); ?>" target="<?php echo esc_attr($target); ?>"<?php echo '' !== $rel ? ' rel="' . esc_attr($rel) . '"' : ''; ?>><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php if (($should_load_tracking_js || $should_load_newsletter_js) && file_exists($js_path)) : ?>
    <script>window.bwLinkPageConfig = <?php echo wp_json_encode($frontend_config); ?>;</script>
    <script src="<?php echo esc_url($js_url); ?>?ver=<?php echo esc_attr((string) filemtime($js_path)); ?>" defer></script>
<?php endif; ?>
</body>
</html>
