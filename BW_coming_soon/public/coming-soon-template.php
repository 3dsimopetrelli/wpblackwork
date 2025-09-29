<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php bloginfo('name'); ?> - Coming Soon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__); ?>css/style.css">
</head>
<body>
    <div class="bw-video-background">
        <video autoplay muted playsinline controls>
            <source src="<?php echo plugin_dir_url(__FILE__); ?>video/coming-soon.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>

    <div class="bw-overlay">
        <div class="bw-text">
            <p><strong>Blackwork.pro</strong> is a Heritage Studio at the intersection of imagination and history. We specialize in sourcing and selling rare books and prints, transforming their illustrations into digital art and handcrafted prints. Our workshops based in Berlin and Bella Venezia serve as a space where we revitalize historical treasures, offering curated digital collections, publications and unique, handcrafted pieces that reflect a deep appreciation for both art and the past.</p>
            
            <!-- Newsletter form -->
            <form class="bw-newsletter" method="post">
                <input type="text" name="bw_name" placeholder="Your name" required>
                <input type="email" name="bw_email" placeholder="Your email" required>
                <label class="bw-privacy">
                    <input type="checkbox" name="bw_privacy" required>
                    I agree to the privacy policy
                </label>
                <p class="privacy-note">Your email will only be used for site purposes.</p>
                <button type="submit" name="bw_subscribe" class="bw-btn">Subscribe</button>
            </form>

            <?php if (isset($_GET['bw_subscribed']) && $_GET['bw_subscribed'] == '1') : ?>
                <p class="bw-success">Thanks for subscribing! Please check your email inbox to confirm your subscription. If you don’t see it, check your spam folder.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
