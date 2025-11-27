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
    add_meta_box(
        'bw_artist_name',
        __( 'Nome Artista', 'bw' ),
        'bw_render_artist_name_metabox',
        'product',
        'side',
        'default'
    );
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

/**
 * Save Artist Name metabox data.
 *
 * @param int $post_id Post ID.
 */
function bw_save_artist_name_metabox( $post_id ) {
    if ( ! isset( $_POST['bw_artist_name_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bw_artist_name_nonce'] ), 'bw_save_artist_name' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['post_type'] ) && 'product' === $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_product', $post_id ) ) {
            return;
        }
    } else {
        return;
    }

    $artist_name_raw = isset( $_POST['_bw_artist_name'] ) ? wp_unslash( $_POST['_bw_artist_name'] ) : '';
    $artist_name     = sanitize_text_field( $artist_name_raw );

    if ( '' !== $artist_name ) {
        update_post_meta( $post_id, '_bw_artist_name', $artist_name );
    } else {
        delete_post_meta( $post_id, '_bw_artist_name' );
    }
}
add_action( 'save_post_product', 'bw_save_artist_name_metabox' );
