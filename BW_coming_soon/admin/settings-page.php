<?php
if (!defined('ABSPATH')) {
    exit;
}

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

function bw_coming_soon_settings_page() {
    if (isset($_POST['bw_coming_soon_toggle'])) {
        update_option('bw_coming_soon_active', $_POST['bw_coming_soon_toggle'] === '1' ? 1 : 0);
        echo '<div class="updated"><p>Impostazioni salvate.</p></div>';
    }

    $active = get_option('bw_coming_soon_active');
    ?>
    <div class="wrap">
        <h1>BW Coming Soon</h1>
        <form method="post">
            <label for="bw_coming_soon_toggle">
                <input type="checkbox" id="bw_coming_soon_toggle" name="bw_coming_soon_toggle" value="1" <?php checked(1, $active); ?> />
                Attiva modalità Coming Soon
            </label>
            <br><br>
            <input type="submit" class="button button-primary" value="Salva impostazioni">
        </form>
    </div>
    <?php
}
?>
