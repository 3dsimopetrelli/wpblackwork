<?php
/**
 * Product Details Metabox for WooCommerce products.
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
 * List of bibliographic fields for prints.
 *
 * @return array<string, string> Key is the meta key, value is the label.
 */
function bw_get_prints_bibliographic_fields() {
    return [
        '_print_artist'     => __( 'Artist', 'bw' ),
        '_print_publisher'  => __( 'Publisher', 'bw' ),
        '_print_year'       => __( 'Year', 'bw' ),
        '_print_technique'  => __( 'Technique', 'bw' ),
        '_print_material'   => __( 'Material', 'bw' ),
        '_print_plate_size' => __( 'Plate Size', 'bw' ),
        '_print_condition'  => __( 'Condition', 'bw' ),
    ];
}

/**
 * List of digital product fields.
 *
 * @return array<string, string> Key is the meta key, value is the label.
 */
function bw_get_digital_product_fields() {
    return [
        '_digital_total_assets' => __( 'Total Assets', 'bw' ),
        '_digital_assets_list'  => __( 'Assets List', 'bw' ),
        '_digital_file_size'    => __( 'File size', 'bw' ),
        '_digital_formats'      => __( 'Formats included', 'bw' ),
        '_digital_source'       => __( 'Source', 'bw' ),
        '_digital_publisher'    => __( 'Publisher', 'bw' ),
        '_digital_year'         => __( 'Year', 'bw' ),
        '_digital_technique'    => __( 'Technique', 'bw' ),
    ];
}

/**
 * Register the Product Details metabox.
 */
function bw_add_bibliographic_details_metabox() {
    add_meta_box(
        'bw_bibliographic_details',
        __( 'Product Details', 'bw' ),
        'bw_render_bibliographic_details_metabox',
        'product',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'bw_add_bibliographic_details_metabox' );

/**
 * Render the Product Details metabox.
 *
 * @param \WP_Post $post Current post object.
 */
function bw_render_bibliographic_details_metabox( $post ) {
    wp_nonce_field( 'bw_save_bibliographic_details', 'bw_bibliographic_details_nonce' );

    $book_fields    = bw_get_bibliographic_fields();
    $print_fields   = bw_get_prints_bibliographic_fields();
    $digital_fields = bw_get_digital_product_fields();
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
        .bw-biblio-section {
            border: 1px solid #dcdcde;
            border-radius: 6px;
            padding: 12px;
            margin-top: 12px;
        }
        .bw-biblio-section:first-of-type {
            margin-top: 0;
        }
        .bw-biblio-section-title {
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: 600;
        }
        .bw-biblio-section-subtitle {
            margin: 0 0 8px;
            font-size: 13px;
            font-weight: 600;
            color: #50575e;
        }
        .bw-biblio-textarea {
            width: 100%;
            box-sizing: border-box;
            min-height: 80px;
        }
    </style>
    <div class="bw-biblio-section bw-biblio-section-digital">
        <h4 class="bw-biblio-section-title"><?php esc_html_e( 'Digital Product Details', 'bw' ); ?></h4>
        <div class="bw-biblio-section-subtitle"><?php esc_html_e( 'Collection content', 'bw' ); ?></div>
        <table class="bw-biblio-metabox-table">
            <tbody>
            <?php foreach ( $digital_fields as $meta_key => $label ) :
                $value = get_post_meta( $post->ID, $meta_key, true );
                ?>
                <tr>
                    <th scope="row"><label for="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( $label ); ?></label></th>
                    <td>
                        <?php if ( '_digital_assets_list' === $meta_key ) : ?>
                            <textarea
                                id="<?php echo esc_attr( $meta_key ); ?>"
                                name="<?php echo esc_attr( $meta_key ); ?>"
                                class="bw-biblio-textarea"
                            ><?php echo esc_textarea( $value ); ?></textarea>
                        <?php else : ?>
                            <input
                                type="text"
                                id="<?php echo esc_attr( $meta_key ); ?>"
                                name="<?php echo esc_attr( $meta_key ); ?>"
                                value="<?php echo esc_attr( $value ); ?>"
                                class="bw-biblio-metabox-input"
                            />
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="bw-biblio-section bw-biblio-section-books">
        <h4 class="bw-biblio-section-title"><?php esc_html_e( 'Books – Bibliographic details', 'bw' ); ?></h4>
        <table class="bw-biblio-metabox-table">
            <tbody>
            <?php foreach ( $book_fields as $meta_key => $label ) :
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
    </div>

    <div class="bw-biblio-section bw-biblio-section-prints">
        <h4 class="bw-biblio-section-title"><?php esc_html_e( 'Prints – Bibliographic details', 'bw' ); ?></h4>
        <table class="bw-biblio-metabox-table">
            <tbody>
            <?php foreach ( $print_fields as $meta_key => $label ) :
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
    </div>
    <?php
}

/**
 * Save Product Details data.
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

    $fields = array_merge( bw_get_bibliographic_fields(), bw_get_prints_bibliographic_fields(), bw_get_digital_product_fields() );

    foreach ( $fields as $meta_key => $label ) {
        $raw_value = isset( $_POST[ $meta_key ] ) ? wp_unslash( $_POST[ $meta_key ] ) : '';
        $value     = '_digital_assets_list' === $meta_key ? sanitize_textarea_field( $raw_value ) : sanitize_text_field( $raw_value );

        if ( '' !== $value ) {
            update_post_meta( $post_id, $meta_key, $value );
        } else {
            delete_post_meta( $post_id, $meta_key );
        }
    }
}
add_action( 'save_post_product', 'bw_save_bibliographic_details_metabox' );
