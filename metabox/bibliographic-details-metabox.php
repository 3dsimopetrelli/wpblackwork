<?php
/**
 * Bibliographic Details Metabox for WooCommerce products.
 *
 * @package BWElementorWidgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * List of bibliographic fields.
 *
 * @return array<string, string> Key is the meta key, value is the label.
 */
function bw_get_bibliographic_fields() {
    return [
        '_bw_biblio_title'     => __( 'Title', 'bw' ),
        '_bw_biblio_author'    => __( 'Author', 'bw' ),
        '_bw_biblio_publisher' => __( 'Publisher', 'bw' ),
        '_bw_biblio_year'      => __( 'Year', 'bw' ),
        '_bw_biblio_language'  => __( 'Language', 'bw' ),
        '_bw_biblio_binding'   => __( 'Binding', 'bw' ),
        '_bw_biblio_pages'     => __( 'Pages', 'bw' ),
        '_bw_biblio_edition'   => __( 'Edition', 'bw' ),
        '_bw_biblio_condition' => __( 'Condition', 'bw' ),
        '_bw_biblio_location'  => __( 'Location', 'bw' ),
    ];
}

/**
 * Register the Bibliographic Details metabox.
 */
function bw_add_bibliographic_details_metabox() {
    add_meta_box(
        'bw_bibliographic_details',
        __( 'Bibliographic Details', 'bw' ),
        'bw_render_bibliographic_details_metabox',
        'product',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'bw_add_bibliographic_details_metabox' );

/**
 * Render the Bibliographic Details metabox.
 *
 * @param \WP_Post $post Current post object.
 */
function bw_render_bibliographic_details_metabox( $post ) {
    wp_nonce_field( 'bw_save_bibliographic_details', 'bw_bibliographic_details_nonce' );

    $fields = bw_get_bibliographic_fields();
    ?>
    <style>
        .bw-biblio-metabox-table {
            width: 100%;
            border-collapse: collapse;
        }
        .bw-biblio-metabox-table th,
        .bw-biblio-metabox-table td {
            padding: 6px 8px;
            vertical-align: middle;
        }
        .bw-biblio-metabox-table th {
            text-align: left;
            width: 30%;
        }
        .bw-biblio-metabox-table tr:nth-child(odd) td,
        .bw-biblio-metabox-table tr:nth-child(odd) th {
            background: #f9f9f9;
        }
        .bw-biblio-metabox-input {
            width: 100%;
            box-sizing: border-box;
        }
    </style>
    <table class="bw-biblio-metabox-table">
        <tbody>
        <?php foreach ( $fields as $meta_key => $label ) :
            $value = get_post_meta( $post->ID, $meta_key, true );
            ?>
            <tr>
                <th scope="row"><label for="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( $label ); ?></label></th>
                <td>
                    <input
                        type="text"
                        id="<?php echo esc_attr( $meta_key ); ?>"
                        name="<?php echo esc_attr( $meta_key ); ?>"
                        value="<?php echo esc_attr( $value ); ?>"
                        class="bw-biblio-metabox-input"
                    />
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Save Bibliographic Details data.
 *
 * @param int $post_id Post ID.
 */
function bw_save_bibliographic_details_metabox( $post_id ) {
    if ( ! isset( $_POST['bw_bibliographic_details_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bw_bibliographic_details_nonce'] ), 'bw_save_bibliographic_details' ) ) {
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

    $fields = bw_get_bibliographic_fields();

    foreach ( $fields as $meta_key => $label ) {
        $raw_value = isset( $_POST[ $meta_key ] ) ? wp_unslash( $_POST[ $meta_key ] ) : '';
        $value     = sanitize_text_field( $raw_value );

        if ( '' !== $value ) {
            update_post_meta( $post_id, $meta_key, $value );
        } else {
            delete_post_meta( $post_id, $meta_key );
        }
    }
}
add_action( 'save_post_product', 'bw_save_bibliographic_details_metabox' );
