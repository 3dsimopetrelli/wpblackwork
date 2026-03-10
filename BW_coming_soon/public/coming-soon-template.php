<?php
if (!defined('ABSPATH')) {
    exit;
}

$bw_coming_soon_channel_settings = function_exists('bw_coming_soon_get_brevo_settings')
    ? bw_coming_soon_get_brevo_settings()
    : [
        'subscribe_enabled' => 0,
        'success_message' => __('Thanks for subscribing! Please check your inbox.', 'bw'),
        'error_message' => __('Unable to subscribe right now. Please try again later.', 'bw'),
    ];

$bw_coming_soon_feedback = isset($_GET['bw_cs']) ? sanitize_key(wp_unslash($_GET['bw_cs'])) : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php bloginfo('name'); ?> - Coming Soon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__); ?>css/style.css">
</head>
<body>
    <div class="bw-video-background">
        <video id="bw-video" autoplay muted playsinline>
            <source src="<?php echo plugin_dir_url(__FILE__); ?>video/opening-threshold-short-3.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>

    <div class="bw-overlay" id="bw-overlay">
        <div class="bw-text">
            <p><strong>Blackwork.pro &#x2609;</strong> heritage studio and digital library.  
An archive spanning centuries of antiquarian material, rare books and prints, curated and prepared for creative use, made available in vector and high-resolution formats for designers, artists, and researchers</p>
            
            <!-- Newsletter form -->
            <?php if (!empty($bw_coming_soon_channel_settings['subscribe_enabled'])) : ?>
            <form class="bw-newsletter" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="bw_coming_soon_subscribe">
                <?php wp_nonce_field('bw_coming_soon_subscribe', 'bw_coming_soon_subscribe_nonce'); ?>
                <div class="bw-input-group">
                    <input type="email" name="bw_email" placeholder="Enter your email address here" required>
                    <button type="submit" name="bw_subscribe" class="bw-btn">Subscribe</button>
                </div>
                <label class="bw-privacy">
                    <input type="checkbox" name="bw_privacy" required>
                    I agree that my email will only be used for Blackwork updates.
                </label>
                <?php if ('ok' === $bw_coming_soon_feedback) : ?>
                    <p class="bw-success"><?php echo esc_html((string) $bw_coming_soon_channel_settings['success_message']); ?></p>
                <?php elseif ('err' === $bw_coming_soon_feedback) : ?>
                    <p class="bw-success"><?php echo esc_html((string) $bw_coming_soon_channel_settings['error_message']); ?></p>
                <?php endif; ?>
            </form>
            <?php endif; ?>
        </div>
    </div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const video = document.getElementById("bw-video");
    const overlay = document.getElementById("bw-overlay");

    // Mostra l'overlay solo quando il video finisce
    video.addEventListener("ended", function() {
        overlay.classList.add("show");
    });
});
</script>
</body>
</html>
