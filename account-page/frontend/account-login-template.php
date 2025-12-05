<?php
/**
 * Template Account Page Login Personalizzato
 *
 * Mostra il layout custom per la pagina di login quando l'utente non è loggato
 *
 * @package BW_Elementor_Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

// Recupera le opzioni dalla tab Account Page
$login_image = bw_get_account_page_option('bw_account_page_login_image', '');
$logo = bw_get_account_page_option('bw_account_page_logo', '');
$facebook_login = bw_get_account_page_option('bw_account_page_facebook_login', 0);
$google_login = bw_get_account_page_option('bw_account_page_google_login', 0);
$description = bw_get_account_page_option('bw_account_page_description', 'Enter your email and we\'ll send you a login code');
$back_text = bw_get_account_page_option('bw_account_page_back_text', 'go back to store');
$back_url = bw_get_account_page_option('bw_account_page_back_url', home_url('/'));

// Se back_url è vuoto, usa home_url
if (empty($back_url)) {
    $back_url = home_url('/');
}

// Mostra social login solo se almeno uno è abilitato
$show_social = $facebook_login || $google_login;

get_header();
?>

<div class="bw-account-page-wrapper">
    <!-- Metà sinistra: Immagine Cover -->
    <div class="bw-account-left" <?php if ($login_image): ?>style="background-image: url('<?php echo esc_url($login_image); ?>');"<?php endif; ?>>
        <!-- Immagine gestita via CSS background -->
    </div>

    <!-- Metà destra: Form Login -->
    <div class="bw-account-right">
        <div class="bw-account-form-container">

            <!-- Logo -->
            <?php if ($logo): ?>
                <div class="bw-account-logo">
                    <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" />
                </div>
            <?php endif; ?>

            <!-- Separatore -->
            <div class="bw-account-separator"></div>

            <!-- Social Login (solo se abilitato) -->
            <?php if ($show_social): ?>
                <div class="bw-account-social-login">
                    <p class="bw-social-label">Log in with</p>
                    <div class="bw-social-buttons">
                        <?php if ($facebook_login): ?>
                            <a href="#" class="bw-social-button bw-social-facebook" data-provider="facebook">
                                Facebook
                            </a>
                        <?php endif; ?>

                        <?php if ($google_login): ?>
                            <a href="#" class="bw-social-button bw-social-google" data-provider="google">
                                Google
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Separatore dopo social login -->
                <div class="bw-account-separator-line">
                    <span>or</span>
                </div>
            <?php endif; ?>

            <!-- Form di Login WooCommerce -->
            <div class="bw-account-login-form">
                <?php
                // Usa il form di login standard di WooCommerce
                if (function_exists('woocommerce_login_form')) {
                    woocommerce_login_form([
                        'redirect' => wc_get_page_permalink('myaccount'),
                    ]);
                } else {
                    // Fallback: form di login WordPress standard
                    wp_login_form([
                        'redirect' => wc_get_page_permalink('myaccount'),
                        'form_id' => 'bw-login-form',
                        'label_username' => __('Username or email address', 'woocommerce'),
                        'label_password' => __('Password', 'woocommerce'),
                        'label_remember' => __('Remember me', 'woocommerce'),
                        'label_log_in' => __('Log In', 'woocommerce'),
                    ]);
                }
                ?>
            </div>

            <!-- Pulsante "Log in Without Password" -->
            <div class="bw-account-passwordless">
                <button type="button" class="bw-button-passwordless" id="bw-passwordless-login">
                    Log in Without Password
                </button>
            </div>

            <!-- Descrizione -->
            <?php if ($description): ?>
                <div class="bw-account-description">
                    <p><?php echo wp_kses_post($description); ?></p>
                </div>
            <?php endif; ?>

            <!-- Link "Go back to store" -->
            <div class="bw-account-back-link">
                <a href="<?php echo esc_url($back_url); ?>">
                    <?php echo esc_html($back_text); ?> →
                </a>
            </div>

        </div>
    </div>
</div>

<?php
get_footer();
