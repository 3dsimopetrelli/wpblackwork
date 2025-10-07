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

    $product_size_mb      = get_post_meta( $post->ID, '_product_size_mb', true );
    $product_assets_count = get_post_meta( $post->ID, '_product_assets_count', true );
    $product_formats      = get_post_meta( $post->ID, '_product_formats', true );
    $product_button_text  = get_post_meta( $post->ID, '_product_button_text', true );
    $product_button_link  = get_post_meta( $post->ID, '_product_button_link', true );
    ?>
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
    <?php
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

    update_post_meta( $post_id, '_product_size_mb', $product_size_mb );
    update_post_meta( $post_id, '_product_assets_count', $product_assets_count );
    update_post_meta( $post_id, '_product_formats', $product_formats );
    update_post_meta( $post_id, '_product_button_text', $product_button_text );
    update_post_meta( $post_id, '_product_button_link', $product_button_link );
}
add_action( 'save_post', 'bw_save_digital_products' );

