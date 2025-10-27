<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Product Slider hover image metabox for WooCommerce products.
 */
function bw_register_product_slider_hover_metabox() {
    add_meta_box(
        'bw_product_slider_image',
        __( 'Product slider (Slick Slider)', 'bw' ),
        'bw_render_product_slider_hover_metabox',
        'product',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes_product', 'bw_register_product_slider_hover_metabox' );

/**
 * Render the Product Slider hover image metabox content.
 *
 * @param \WP_Post $post Current post object.
 */
function bw_render_product_slider_hover_metabox( $post ) {
    wp_enqueue_media();

    $image_id  = (int) get_post_meta( $post->ID, '_bw_slider_hover_image', true );
    $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';

    wp_nonce_field( 'bw_slider_hover_image_nonce', 'bw_slider_hover_image_nonce' );
    ?>
    <div class="bw-slider-metabox-wrapper">
        <div class="bw-slider-metabox-preview" style="margin-bottom:10px;">
            <?php if ( $image_url ) : ?>
                <img src="<?php echo esc_url( $image_url ); ?>" style="max-width:100%;border-radius:6px;" alt="<?php echo esc_attr__( 'Slider hover preview', 'bw' ); ?>">
            <?php endif; ?>
        </div>
        <input type="hidden" name="bw_slider_hover_image" id="bw_slider_hover_image" value="<?php echo esc_attr( $image_id ); ?>">
        <button type="button" class="button bw-upload-slider-hover"><?php esc_html_e( 'Upload Image', 'bw' ); ?></button>
        <button type="button" class="button bw-remove-slider-hover" style="display:<?php echo $image_url ? 'inline-block' : 'none'; ?>;">
            <?php esc_html_e( 'Remove', 'bw' ); ?>
        </button>
    </div>

    <script>
    jQuery(function($){
        var frame;
        $('.bw-upload-slider-hover').on('click', function(e){
            e.preventDefault();

            if ( frame ) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: '<?php echo esc_js( __( 'Select or Upload Hover Image', 'bw' ) ); ?>',
                button: { text: '<?php echo esc_js( __( 'Use this image', 'bw' ) ); ?>' },
                multiple: false
            });

            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#bw_slider_hover_image').val(attachment.id);
                $('.bw-slider-metabox-preview').html('<img src="' + attachment.url + '" style="max-width:100%;border-radius:6px;" alt="<?php echo esc_js( __( 'Slider hover preview', 'bw' ) ); ?>">');
                $('.bw-remove-slider-hover').show();
            });

            frame.open();
        });

        $('.bw-remove-slider-hover').on('click', function(e){
            e.preventDefault();
            $('#bw_slider_hover_image').val('');
            $('.bw-slider-metabox-preview').empty();
            $(this).hide();
        });
    });
    </script>
    <?php
}

/**
 * Save the Product Slider hover image metabox value.
 *
 * @param int $post_id Post ID.
 */
function bw_save_product_slider_hover_metabox( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! isset( $_POST['post_type'] ) || 'product' !== sanitize_key( wp_unslash( $_POST['post_type'] ) ) ) {
        return;
    }

    if ( ! isset( $_POST['bw_slider_hover_image_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bw_slider_hover_image_nonce'] ) ), 'bw_slider_hover_image_nonce' ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['bw_slider_hover_image'] ) ) {
        $image_id = absint( wp_unslash( $_POST['bw_slider_hover_image'] ) );

        if ( $image_id ) {
            update_post_meta( $post_id, '_bw_slider_hover_image', $image_id );
        } else {
            delete_post_meta( $post_id, '_bw_slider_hover_image' );
        }
    }
}
add_action( 'save_post_product', 'bw_save_product_slider_hover_metabox' );
