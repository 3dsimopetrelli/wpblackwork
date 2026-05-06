<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings = function_exists('bw_link_page_get_settings') ? bw_link_page_get_settings() : [];
$links = isset($settings['links']) && is_array($settings['links']) ? $settings['links'] : [];
$title = isset($settings['title']) ? (string) $settings['title'] : '';
$description = isset($settings['description']) ? (string) $settings['description'] : '';
$logo_id = isset($settings['logo_id']) ? (int) $settings['logo_id'] : 0;
$logo_url = $logo_id > 0 ? wp_get_attachment_image_url($logo_id, 'full') : '';
$page_id = isset($settings['page_id']) ? (int) $settings['page_id'] : 0;

$socials = isset($settings['socials']) && is_array($settings['socials']) ? $settings['socials'] : [];
$social_map = [
    'instagram' => 'Instagram',
    'youtube' => 'YouTube',
    'pinterest' => 'Pinterest',
];

$has_socials = false;
foreach ($social_map as $key => $_label) {
    if (!empty($socials[$key]['enabled']) && !empty($socials[$key]['url'])) {
        $has_socials = true;
        break;
    }
}

$css_path = plugin_dir_path(__FILE__) . '../assets/link-page.css';
$css_url = plugin_dir_url(__FILE__) . '../assets/link-page.css';
$js_path = plugin_dir_path(__FILE__) . '../assets/link-page.js';
$js_url = plugin_dir_url(__FILE__) . '../assets/link-page.js';

$analytics_config = [
    'endpoint' => admin_url('admin-ajax.php'),
    'action' => 'bw_link_page_track_click',
    'nonce' => wp_create_nonce('bw_link_page_track_click'),
    'pageId' => $page_id,
];
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html(get_the_title()); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url($css_url); ?>?ver=<?php echo esc_attr((string) (file_exists($css_path) ? filemtime($css_path) : '1.0.0')); ?>">
</head>
<body>
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

        <div class="links">
            <?php foreach ($links as $index => $link) :
                $label = isset($link['label']) ? (string) $link['label'] : '';
                $url = isset($link['url']) ? (string) $link['url'] : '';
                if ('' === $label || '' === $url) {
                    continue;
                }
                $target = !empty($link['target']) ? '_blank' : '_self';
                $rel = '_blank' === $target ? 'noopener noreferrer' : '';
                $link_id = function_exists('bw_link_page_build_link_id') ? bw_link_page_build_link_id($link, $index) : ('link-' . (string) $index);
                ?>
                <a class="link-item"
                    href="<?php echo esc_url($url); ?>"
                    target="<?php echo esc_attr($target); ?>"
                    data-bw-link-id="<?php echo esc_attr($link_id); ?>"
                    data-bw-link-label="<?php echo esc_attr($label); ?>"
                    <?php echo '' !== $rel ? ' rel="' . esc_attr($rel) . '"' : ''; ?>>
                    <?php echo esc_html($label); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($has_socials) : ?>
            <div class="socials">
                <?php foreach ($social_map as $key => $label) :
                    $item = isset($socials[$key]) && is_array($socials[$key]) ? $socials[$key] : [];
                    $enabled = !empty($item['enabled']);
                    $url = isset($item['url']) ? (string) $item['url'] : '';
                    if (!$enabled || '' === $url) {
                        continue;
                    }
                    ?>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php if (file_exists($js_path)) : ?>
    <script>window.bwLinkPageAnalytics = <?php echo wp_json_encode($analytics_config); ?>;</script>
    <script src="<?php echo esc_url($js_url); ?>?ver=<?php echo esc_attr((string) filemtime($js_path)); ?>" defer></script>
<?php endif; ?>
</body>
</html>
