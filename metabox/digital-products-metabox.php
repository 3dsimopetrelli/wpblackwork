<?php
/**
 * Digital Products Metabox for WooCommerce products.
 *
 * @package BWElementorWidgets
 * @author  Simone
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Digital Products metabox.
 */
function bw_add_digital_products_metabox() {
    add_meta_box(
        'bw-digital-products-metabox',
        __( 'Metabox Digital Products', 'bw' ),
        'bw_render_digital_products_metabox',
        'product',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'bw_add_digital_products_metabox' );

/**
 * Render the Digital Products metabox.
 *
 * @param \WP_Post $post Current post object.
 */
function bw_render_digital_products_metabox( $post ) {
    wp_nonce_field( 'bw_save_digital_products', 'bw_digital_products_nonce' );

    $showcase_image       = get_post_meta( $post->ID, '_bw_showcase_image', true );
    if ( empty( $showcase_image ) ) {
        $legacy_showcase_image = get_post_meta( $post->ID, '_product_showcase_image', true );
        if ( $legacy_showcase_image ) {
            $showcase_image = $legacy_showcase_image;
        }
    }
    $showcase_title       = get_post_meta( $post->ID, '_bw_showcase_title', true );
    $showcase_description = get_post_meta( $post->ID, '_bw_showcase_description', true );
    $product_size_mb      = get_post_meta( $post->ID, '_product_size_mb', true );
    $product_assets_count = get_post_meta( $post->ID, '_product_assets_count', true );
    $product_formats      = get_post_meta( $post->ID, '_product_formats', true );
    $product_button_text  = get_post_meta( $post->ID, '_product_button_text', true );
    $product_button_link  = get_post_meta( $post->ID, '_product_button_link', true );
    $product_color        = get_post_meta( $post->ID, '_product_color', true );

    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }

    $preview_style = $showcase_image ? 'display:block;' : 'display:none;';
    ?>
    <div class="bw-digital-products-showcase-field">
        <label><strong><?php esc_html_e( 'Showcase Image', 'bw' ); ?></strong></label><br>
        <img id="bw_showcase_image_preview" src="<?php echo esc_url( $showcase_image ); ?>" style="max-width:100%;margin-top:6px;<?php echo esc_attr( $preview_style ); ?>">
        <input type="hidden" id="bw_showcase_image" name="bw_showcase_image" value="<?php echo esc_attr( $showcase_image ); ?>">
        <button type="button" class="button bw-upload-image"><?php esc_html_e( 'Scegli immagine', 'bw' ); ?></button>
        <button type="button" class="button bw-remove-image"><?php esc_html_e( 'Rimuovi', 'bw' ); ?></button>
    </div>
    <p>
        <label for="bw_showcase_title"><?php esc_html_e( 'Showcase Title', 'bw' ); ?></label>
        <input type="text" id="bw_showcase_title" name="bw_showcase_title" value="<?php echo esc_attr( $showcase_title ); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="bw_showcase_description"><?php esc_html_e( 'Showcase Description', 'bw' ); ?></label>
        <textarea id="bw_showcase_description" name="bw_showcase_description" rows="4" style="width:100%;"><?php echo esc_textarea( $showcase_description ); ?></textarea>
    </p>
    <p>
        <label for="bw_product_size_mb"><?php esc_html_e( 'File Size (MB)', 'bw' ); ?></label>
        <input type="text" id="bw_product_size_mb" name="bw_product_size_mb" value="<?php echo esc_attr( $product_size_mb ); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="bw_product_assets_count"><?php esc_html_e( 'Assets Count', 'bw' ); ?></label>
        <input type="number" id="bw_product_assets_count" name="bw_product_assets_count" value="<?php echo esc_attr( $product_assets_count ); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="bw_product_formats"><?php esc_html_e( 'Formats (comma separated)', 'bw' ); ?></label>
        <input type="text" id="bw_product_formats" name="bw_product_formats" value="<?php echo esc_attr( $product_formats ); ?>" style="width:100%;" />
        <small style="color:#777;display:block;margin-top:4px;">
            <?php esc_html_e( 'Inserisci i formati separati da una virgola (es. SVG, PSD, PNG)', 'bw' ); ?>
        </small>
    </p>
    <p>
        <label for="bw_product_button_text"><?php esc_html_e( 'Button Text', 'bw' ); ?></label>
        <input type="text" id="bw_product_button_text" name="bw_product_button_text" value="<?php echo esc_attr( $product_button_text ); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="bw_product_button_link"><?php esc_html_e( 'Button Link (URL)', 'bw' ); ?></label>
        <input type="url" id="bw_product_button_link" name="bw_product_button_link" value="<?php echo esc_attr( $product_button_link ); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="bw_product_color"><?php esc_html_e( 'Colore', 'bw' ); ?></label>
        <input type="color" id="bw_product_color" name="bw_product_color" value="<?php echo esc_attr( $product_color ? $product_color : '#ffffff' ); ?>" style="width:100%;" />
    </p>
    <?php
    bw_render_showcase_image_field_script();
}

/**
 * Save the Digital Products metabox data.
 *
 * @param int $post_id Post ID.
 */
function bw_save_digital_products( $post_id ) {
    // Verify nonce and autosave conditions.
    if ( ! isset( $_POST['bw_digital_products_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['bw_digital_products_nonce'] ), 'bw_save_digital_products' ) ) {
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

    // Sanitize and save each field.
    $product_size_mb      = isset( $_POST['bw_product_size_mb'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_product_size_mb'] ) ) : '';
    $product_assets_count = '';
    if ( isset( $_POST['bw_product_assets_count'] ) && '' !== $_POST['bw_product_assets_count'] ) {
        $product_assets_count = intval( wp_unslash( $_POST['bw_product_assets_count'] ) );
    }
    $product_formats      = isset( $_POST['bw_product_formats'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_product_formats'] ) ) : '';
    $product_button_text  = isset( $_POST['bw_product_button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_product_button_text'] ) ) : '';
    $product_button_link  = isset( $_POST['bw_product_button_link'] ) ? esc_url_raw( wp_unslash( $_POST['bw_product_button_link'] ) ) : '';
    $product_color        = isset( $_POST['bw_product_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['bw_product_color'] ) ) : '';
    $showcase_image       = isset( $_POST['bw_showcase_image'] ) ? esc_url_raw( wp_unslash( $_POST['bw_showcase_image'] ) ) : '';
    $showcase_title       = isset( $_POST['bw_showcase_title'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_showcase_title'] ) ) : '';
    $showcase_description = isset( $_POST['bw_showcase_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bw_showcase_description'] ) ) : '';

    update_post_meta( $post_id, '_product_size_mb', $product_size_mb );
    update_post_meta( $post_id, '_product_assets_count', $product_assets_count );
    update_post_meta( $post_id, '_product_formats', $product_formats );
    update_post_meta( $post_id, '_product_button_text', $product_button_text );
    update_post_meta( $post_id, '_product_button_link', $product_button_link );
    update_post_meta( $post_id, '_product_color', $product_color );
    update_post_meta( $post_id, '_bw_showcase_image', $showcase_image );
    update_post_meta( $post_id, '_bw_showcase_title', $showcase_title );
    update_post_meta( $post_id, '_bw_showcase_description', $showcase_description );
}
add_action( 'save_post', 'bw_save_digital_products' );

/**
 * Print the JavaScript required for the showcase media uploader.
 */
function bw_render_showcase_image_field_script() {
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

