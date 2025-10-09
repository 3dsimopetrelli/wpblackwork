<?php
/**
 * Images Showcase Metabox
 * Aggiunge un metabox per gestire un’immagine dedicata alle Showcase Slides.
 * Autore: GPT Agent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Images Showcase metabox for WooCommerce products.
 */
function bw_add_images_showcase_metabox() {
    add_meta_box(
        'bw-images-showcase-metabox',
        __( 'Images Showcase', 'bw' ),
        'bw_render_images_showcase_metabox',
        'product',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'bw_add_images_showcase_metabox' );

/**
 * Render the Images Showcase metabox.
 *
 * @param \WP_Post $post Current post object.
 */
function bw_render_images_showcase_metabox( $post ) {
    wp_nonce_field( 'bw_save_images_showcase', 'bw_images_showcase_nonce' );

    $showcase_image = get_post_meta( $post->ID, '_product_showcase_image', true );

    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }

    $preview_style = $showcase_image ? 'display:block;' : 'display:none;';
    ?>
    <p>
        <label><strong><?php esc_html_e( 'Showcase Image:', 'bw' ); ?></strong></label><br>
        <img id="bw_showcase_image_preview" src="<?php echo esc_url( $showcase_image ); ?>" style="max-width:100%;margin-top:6px;<?php echo esc_attr( $preview_style ); ?>">
        <input type="hidden" id="bw_showcase_image" name="bw_showcase_image" value="<?php echo esc_attr( $showcase_image ); ?>">
        <button type="button" class="button bw-upload-image"><?php esc_html_e( 'Scegli immagine', 'bw' ); ?></button>
        <button type="button" class="button bw-remove-image"><?php esc_html_e( 'Rimuovi', 'bw' ); ?></button>
    </p>
    <?php
    bw_print_showcase_metabox_script();
}

/**
 * Print the JavaScript required for the media uploader.
 */
function bw_print_showcase_metabox_script() {
    static $printed = false;

    if ( $printed ) {
        return;
    }

    $printed = true;
    ?>
    <script>
        ( function( $ ) {
            'use strict';

            function resetPreview( container ) {
                container.find( '#bw_showcase_image' ).val( '' );
                container.find( '#bw_showcase_image_preview' )
                    .attr( 'src', '' )
                    .hide();
            }

            $( document ).on( 'click', '.bw-upload-image', function( event ) {
                event.preventDefault();

                var $container = $( this ).closest( '.postbox' );

                var frame = wp.media({
                    title: '<?php echo esc_js( __( 'Seleziona un\'immagine', 'bw' ) ); ?>',
                    button: {
                        text: '<?php echo esc_js( __( 'Usa questa immagine', 'bw' ) ); ?>'
                    },
                    multiple: false
                });

                frame.on( 'select', function() {
                    var attachment = frame.state().get( 'selection' ).first().toJSON();

                    $container.find( '#bw_showcase_image' ).val( attachment.url );
                    $container.find( '#bw_showcase_image_preview' )
                        .attr( 'src', attachment.url )
                        .show();
                });

                frame.open();
            });

            $( document ).on( 'click', '.bw-remove-image', function( event ) {
                event.preventDefault();

                var $container = $( this ).closest( '.postbox' );
                resetPreview( $container );
            });
        } )( jQuery );
    </script>
    <?php
}

/**
 * Save the Images Showcase metabox data.
 *
 * @param int $post_id Current post ID.
 */
function bw_save_images_showcase_metabox( $post_id ) {
    if ( ! isset( $_POST['bw_images_showcase_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['bw_images_showcase_nonce'] ), 'bw_save_images_showcase' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! isset( $_POST['post_type'] ) || 'product' !== $_POST['post_type'] ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $showcase_image = isset( $_POST['bw_showcase_image'] ) ? esc_url_raw( wp_unslash( $_POST['bw_showcase_image'] ) ) : '';

    update_post_meta( $post_id, '_product_showcase_image', $showcase_image );
}
add_action( 'save_post', 'bw_save_images_showcase_metabox' );
