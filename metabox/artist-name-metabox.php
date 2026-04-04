<?php
/**
 * Artist Name Metabox for WooCommerce products.
 *
 * @package BWElementorWidgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Artist Name metabox.
 */
function bw_add_artist_name_metabox() {
    // Artist is now managed inside the Product Details metabox.
    // Avoid registering a second field with the same meta key on the product form.
    return;
}
add_action( 'add_meta_boxes', 'bw_add_artist_name_metabox' );

/**
 * Render the Artist Name metabox.
 *
 * @param \WP_Post $post Current post object.
 */
function bw_render_artist_name_metabox( $post ) {
    wp_nonce_field( 'bw_save_artist_name', 'bw_artist_name_nonce' );

    $artist_name = get_post_meta( $post->ID, '_bw_artist_name', true );
    ?>
    <p>
        <label for="bw_artist_name_field"><?php esc_html_e( 'Nome Artista', 'bw' ); ?></label>
        <input
            type="text"
            id="bw_artist_name_field"
            name="_bw_artist_name"
            value="<?php echo esc_attr( $artist_name ); ?>"
            class="widefat"
            placeholder="<?php esc_attr_e( 'Nome Artista', 'bw' ); ?>"
        />
    </p>
    <?php
}

// Saving is handled by the Product Details metabox save handler.
