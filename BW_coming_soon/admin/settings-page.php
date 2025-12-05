<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NOTA: Questa registrazione è stata disabilitata perché il menu è ora unificato
 * nella pagina "Blackwork Site" sotto Settings
 */
/*
function bw_coming_soon_menu() {
    add_menu_page(
        'BW Coming Soon',
        'BW Coming Soon',
        'manage_options',
        'bw-coming-soon',
        'bw_coming_soon_settings_page',
        'dashicons-clock',
        100
    );
}
add_action('admin_menu', 'bw_coming_soon_menu');
*/

function bw_coming_soon_settings_page() {
    if (isset($_POST['bw_coming_soon_submit'])) {
        check_admin_referer('bw_coming_soon_save', 'bw_coming_soon_nonce');

        $active_value = isset($_POST['bw_coming_soon_toggle']) ? 1 : 0;
        update_option('bw_coming_soon_active', $active_value);
        echo '<div class="updated"><p>Impostazioni salvate.</p></div>';
    }

    $active = (int) get_option('bw_coming_soon_active', 0);
    ?>
    <div class="wrap">
        <h1>BW Coming Soon</h1>
        <form method="post">
            <?php wp_nonce_field('bw_coming_soon_save', 'bw_coming_soon_nonce'); ?>
            <label for="bw_coming_soon_toggle">
                <input type="checkbox" id="bw_coming_soon_toggle" name="bw_coming_soon_toggle" value="1" <?php checked(1, $active); ?> />
                Attiva modalità Coming Soon
            </label>
            <br><br>
            <input type="submit" name="bw_coming_soon_submit" class="button button-primary" value="Salva impostazioni">
        </form>
    </div>
    <?php
}
?>
