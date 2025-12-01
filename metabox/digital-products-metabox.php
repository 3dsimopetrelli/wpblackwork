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
        'bw_digital_products',
        __( 'Metabox Slide Showcase', 'bw' ),
        'bw_render_digital_products_metabox',
        'product',
        'normal',
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

    $showcase_image = get_post_meta( $post->ID, '_bw_showcase_image', true );
    if ( empty( $showcase_image ) ) {
        $legacy_showcase_image = get_post_meta( $post->ID, '_product_showcase_image', true );
        if ( $legacy_showcase_image ) {
            $showcase_image = $legacy_showcase_image;
        }
    }

    $image_id  = 0;
    $image_url = '';
    if ( $showcase_image ) {
        if ( is_numeric( $showcase_image ) ) {
            $image_id  = absint( $showcase_image );
            $image_url = wp_get_attachment_url( $image_id );
        } else {
            $image_url     = esc_url_raw( $showcase_image );
            $maybe_image_id = attachment_url_to_postid( $showcase_image );
            if ( $maybe_image_id ) {
                $image_id = $maybe_image_id;
            }
        }
    }

    $showcase_title       = get_post_meta( $post->ID, '_bw_showcase_title', true );
    $showcase_description = get_post_meta( $post->ID, '_bw_showcase_description', true );
    $showcase_label_enabled = get_post_meta( $post->ID, '_bw_showcase_label_enabled', true );
    $showcase_label       = get_post_meta( $post->ID, '_bw_showcase_label', true );
    $product_type         = get_post_meta( $post->ID, '_bw_product_type', true );
    if ( ! in_array( $product_type, array( 'digital', 'physical' ), true ) ) {
        $product_type = 'digital';
    }

    $file_size      = get_post_meta( $post->ID, '_bw_file_size', true );
    $assets_count   = get_post_meta( $post->ID, '_bw_assets_count', true );
    $formats        = get_post_meta( $post->ID, '_bw_formats', true );
    $info_1         = get_post_meta( $post->ID, '_bw_info_1', true );
    $info_2         = get_post_meta( $post->ID, '_bw_info_2', true );
    $product_button_text = get_post_meta( $post->ID, '_product_button_text', true );
    $product_button_link = get_post_meta( $post->ID, '_product_button_link', true );
    $texts_color         = get_post_meta( $post->ID, '_bw_texts_color', true );
    $showcase_linked_product = get_post_meta( $post->ID, '_bw_showcase_linked_product', true );

    if ( '' === $file_size ) {
        $file_size = get_post_meta( $post->ID, '_product_size_mb', true );
    }

    if ( '' === $assets_count ) {
        $assets_count = get_post_meta( $post->ID, '_product_assets_count', true );
    }

    if ( '' === $formats ) {
        $formats = get_post_meta( $post->ID, '_product_formats', true );
    }

    if ( '' === $texts_color ) {
        $texts_color = get_post_meta( $post->ID, '_product_color', true );
    }

    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }

    $preview_style = ( $image_id || $image_url ) ? 'display:block;' : 'display:none;';
    ?>
    <div class="bw-digital-products-showcase-field">
        <label><strong><?php esc_html_e( 'Showcase Image', 'bw' ); ?></strong></label><br>
        <div id="bw_showcase_image_preview" style="margin-top:6px;<?php echo esc_attr( $preview_style ); ?>">
            <?php
            if ( $image_id ) {
                echo wp_get_attachment_image( $image_id, array( 120, 120 ), false, array(
                    'style' => 'border-radius:6px;width:120px;height:120px;object-fit:cover;',
                ) );
            } elseif ( $image_url ) {
                echo "<img src=\"" . esc_url( $image_url ) . "\" style=\"border-radius:6px;width:120px;height:120px;object-fit:cover;\" alt=\"\" />";
            }
            ?>
        </div>
        <input type="hidden" id="bw_showcase_image" name="bw_showcase_image" value="<?php echo esc_attr( $showcase_image ); ?>">
        <button type="button" class="button bw-upload-image"><?php esc_html_e( 'Scegli immagine', 'bw' ); ?></button>
        <button type="button" class="button bw-remove-image"><?php esc_html_e( 'Rimuovi', 'bw' ); ?></button>
    </div>
    <p>
        <label for="bw_product_type"><strong><?php esc_html_e( 'Product Type', 'bw' ); ?></strong></label><br>
        <select name="bw_product_type" id="bw_product_type" style="min-width:200px;">
            <option value="digital" <?php selected( $product_type, 'digital' ); ?>><?php esc_html_e( 'Digital product', 'bw' ); ?></option>
            <option value="physical" <?php selected( $product_type, 'physical' ); ?>><?php esc_html_e( 'Physical product', 'bw' ); ?></option>
        </select>
    </p>
    <p>
        <label for="bw_showcase_title"><?php esc_html_e( 'Showcase Title', 'bw' ); ?></label>
        <input type="text" id="bw_showcase_title" name="bw_showcase_title" value="<?php echo esc_attr( $showcase_title ); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="bw_showcase_description"><?php esc_html_e( 'Showcase Description', 'bw' ); ?></label>
        <textarea id="bw_showcase_description" name="bw_showcase_description" rows="4" style="width:100%;"><?php echo esc_textarea( $showcase_description ); ?></textarea>
    </p>
    <p>
        <label for="bw_showcase_label_enabled">
            <input type="checkbox" id="bw_showcase_label_enabled" name="bw_showcase_label_enabled" value="yes" <?php checked( $showcase_label_enabled, 'yes' ); ?> />
            <strong><?php esc_html_e( 'Abilita Label Showcase personalizzata', 'bw' ); ?></strong>
        </label>
        <br>
        <small style="color:#777;display:block;margin-top:4px;">
            <?php esc_html_e( 'Se attivato, la label sotto avrà priorità su quella impostata nel widget BW Static Showcase.', 'bw' ); ?>
        </small>
    </p>
    <p>
        <label for="bw_showcase_label"><?php esc_html_e( 'Showcase Label', 'bw' ); ?></label>
        <input type="text" id="bw_showcase_label" name="bw_showcase_label" value="<?php echo esc_attr( $showcase_label ); ?>" style="width:100%;" />
        <small style="color:#777;display:block;margin-top:4px;">
            <?php esc_html_e( 'Testo da visualizzare sopra l\'immagine principale nello showcase. Visibile solo se il toggle sopra è attivo.', 'bw' ); ?>
        </small>
    </p>
    <div class="bw-digital-fields">
        <p>
            <label for="bw_file_size"><?php esc_html_e( 'File Size (MB)', 'bw' ); ?></label>
            <input type="text" id="bw_file_size" name="bw_file_size" value="<?php echo esc_attr( $file_size ); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="bw_assets_count"><?php esc_html_e( 'Assets Count', 'bw' ); ?></label>
            <input type="number" id="bw_assets_count" name="bw_assets_count" value="<?php echo esc_attr( $assets_count ); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="bw_formats"><?php esc_html_e( 'Formats (comma separated)', 'bw' ); ?></label>
            <input type="text" id="bw_formats" name="bw_formats" value="<?php echo esc_attr( $formats ); ?>" style="width:100%;" />
            <small style="color:#777;display:block;margin-top:4px;">
                <?php esc_html_e( 'Inserisci i formati separati da una virgola (es. SVG, PSD, PNG)', 'bw' ); ?>
            </small>
        </p>
    </div>
    <div class="bw-physical-fields">
        <p>
            <label for="bw_info_1"><?php esc_html_e( 'Info 1', 'bw' ); ?></label>
            <input type="text" id="bw_info_1" name="bw_info_1" value="<?php echo esc_attr( $info_1 ); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="bw_info_2"><?php esc_html_e( 'Info 2', 'bw' ); ?></label>
            <input type="text" id="bw_info_2" name="bw_info_2" value="<?php echo esc_attr( $info_2 ); ?>" style="width:100%;" />
        </p>
    </div>
    <p>
        <label for="bw_product_button_text"><?php esc_html_e( 'Button Text', 'bw' ); ?></label>
        <input type="text" id="bw_product_button_text" name="bw_product_button_text" value="<?php echo esc_attr( $product_button_text ); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="bw_product_button_link"><?php esc_html_e( 'Button Link (URL)', 'bw' ); ?></label>
        <input type="url" id="bw_product_button_link" name="bw_product_button_link" value="<?php echo esc_attr( $product_button_link ); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="bw_texts_color"><?php esc_html_e( 'Texts color', 'bw' ); ?></label>
        <input type="color" id="bw_texts_color" name="bw_texts_color" value="<?php echo esc_attr( $texts_color ? $texts_color : '#ffffff' ); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="bw_showcase_linked_product"><strong><?php esc_html_e( 'Prodotto collegato per Showcase', 'bw' ); ?></strong></label><br>
        <select name="bw_showcase_linked_product" id="bw_showcase_linked_product" class="bw-product-search-select" style="width:100%;">
            <option value=""><?php esc_html_e( 'Nessun prodotto selezionato', 'bw' ); ?></option>
            <?php
            if ( $showcase_linked_product ) {
                $linked_product = get_post( $showcase_linked_product );
                if ( $linked_product && 'product' === $linked_product->post_type ) {
                    echo '<option value="' . esc_attr( $showcase_linked_product ) . '" selected="selected">' . esc_html( $linked_product->post_title ) . '</option>';
                }
            }
            ?>
        </select>
        <small style="color:#777;display:block;margin-top:4px;">
            <?php esc_html_e( 'Seleziona un prodotto da mostrare nel widget BW Static Showcase quando lo switch "Usa prodotto da Metabox" è attivo.', 'bw' ); ?>
        </small>
    </p>
    <script>
    (function(){
      function initBwProductTypeToggle() {
        var select = document.querySelector('#bw_product_type');
        var digitalFields = document.querySelectorAll('.bw-digital-fields');
        var physicalFields = document.querySelectorAll('.bw-physical-fields');

        function toggleFields() {
          if (!select) {
            return;
          }

          if (select.value === 'digital') {
            digitalFields.forEach(function(el){ el.style.display = 'block'; });
            physicalFields.forEach(function(el){ el.style.display = 'none'; });
          } else {
            digitalFields.forEach(function(el){ el.style.display = 'none'; });
            physicalFields.forEach(function(el){ el.style.display = 'block'; });
          }
        }

        if (select) {
          select.addEventListener('change', toggleFields);
          toggleFields();
        }
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBwProductTypeToggle);
      } else {
        initBwProductTypeToggle();
      }
    })();

    // Initialize Select2 for product search
    (function($){
      if (typeof $.fn.select2 !== 'undefined') {
        $(document).ready(function(){
          $('#bw_showcase_linked_product').select2({
            ajax: {
              url: ajaxurl,
              dataType: 'json',
              delay: 250,
              data: function(params) {
                return {
                  q: params.term,
                  action: 'bw_search_products',
                  nonce: '<?php echo esc_js( wp_create_nonce( 'bw_search_products' ) ); ?>'
                };
              },
              processResults: function(data) {
                return {
                  results: data
                };
              },
              cache: true
            },
            minimumInputLength: 2,
            placeholder: '<?php echo esc_js( __( 'Cerca un prodotto...', 'bw' ) ); ?>',
            allowClear: true
          });
        });
      }
    })(jQuery);
    </script>
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
    $product_type = isset( $_POST['bw_product_type'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_product_type'] ) ) : 'digital';
    if ( ! in_array( $product_type, array( 'digital', 'physical' ), true ) ) {
        $product_type = 'digital';
    }

    $file_size    = isset( $_POST['bw_file_size'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_file_size'] ) ) : '';
    $assets_count = '';
    if ( isset( $_POST['bw_assets_count'] ) && '' !== $_POST['bw_assets_count'] ) {
        $assets_count = absint( wp_unslash( $_POST['bw_assets_count'] ) );
    }
    $formats       = isset( $_POST['bw_formats'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_formats'] ) ) : '';
    $info_1        = isset( $_POST['bw_info_1'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_info_1'] ) ) : '';
    $info_2        = isset( $_POST['bw_info_2'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_info_2'] ) ) : '';
    $product_button_text = isset( $_POST['bw_product_button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_product_button_text'] ) ) : '';
    $product_button_link = isset( $_POST['bw_product_button_link'] ) ? esc_url_raw( wp_unslash( $_POST['bw_product_button_link'] ) ) : '';
    $texts_color_raw     = isset( $_POST['bw_texts_color'] ) ? wp_unslash( $_POST['bw_texts_color'] ) : '';
    $texts_color         = $texts_color_raw ? sanitize_hex_color( $texts_color_raw ) : '';

    $showcase_image_value = isset( $_POST['bw_showcase_image'] ) ? wp_unslash( $_POST['bw_showcase_image'] ) : '';
    $showcase_image       = '';
    if ( '' !== $showcase_image_value ) {
        if ( is_numeric( $showcase_image_value ) ) {
            $showcase_image = absint( $showcase_image_value );
        } else {
            $showcase_image = esc_url_raw( $showcase_image_value );
        }
    }

    $showcase_title       = isset( $_POST['bw_showcase_title'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_showcase_title'] ) ) : '';
    $showcase_description = isset( $_POST['bw_showcase_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bw_showcase_description'] ) ) : '';
    $showcase_label_enabled = isset( $_POST['bw_showcase_label_enabled'] ) && 'yes' === $_POST['bw_showcase_label_enabled'] ? 'yes' : '';
    $showcase_label       = isset( $_POST['bw_showcase_label'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_showcase_label'] ) ) : '';
    $showcase_linked_product = isset( $_POST['bw_showcase_linked_product'] ) ? absint( wp_unslash( $_POST['bw_showcase_linked_product'] ) ) : 0;

    update_post_meta( $post_id, '_bw_product_type', $product_type );
    update_post_meta( $post_id, '_bw_file_size', $file_size );
    update_post_meta( $post_id, '_bw_assets_count', $assets_count );
    update_post_meta( $post_id, '_bw_formats', $formats );
    update_post_meta( $post_id, '_bw_info_1', $info_1 );
    update_post_meta( $post_id, '_bw_info_2', $info_2 );
    update_post_meta( $post_id, '_bw_texts_color', $texts_color );
    update_post_meta( $post_id, '_product_button_text', $product_button_text );
    update_post_meta( $post_id, '_product_button_link', $product_button_link );
    update_post_meta( $post_id, '_bw_showcase_image', $showcase_image );
    update_post_meta( $post_id, '_bw_showcase_title', $showcase_title );
    update_post_meta( $post_id, '_bw_showcase_description', $showcase_description );
    update_post_meta( $post_id, '_bw_showcase_label_enabled', $showcase_label_enabled );
    update_post_meta( $post_id, '_bw_showcase_label', $showcase_label );
    update_post_meta( $post_id, '_bw_showcase_linked_product', $showcase_linked_product );

    // Legacy compatibility.
    update_post_meta( $post_id, '_product_size_mb', $file_size );
    update_post_meta( $post_id, '_product_assets_count', $assets_count );
    update_post_meta( $post_id, '_product_formats', $formats );
    update_post_meta( $post_id, '_product_color', $texts_color );
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

    // Enqueue Select2 for product search
    wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0' );
    wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [ 'jquery' ], '4.1.0', true );

    ?>
    <script>
        ( function( $ ) {
            'use strict';

            function resetPreview( container ) {
                container.find( '#bw_showcase_image' ).val( '' );
                container.find( '#bw_showcase_image_preview' )
                    .empty()
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
                    var imageStyle = 'border-radius:6px;width:120px;height:120px;object-fit:cover;';
                    var previewUrl = attachment.url;

                    if ( attachment.sizes ) {
                        if ( attachment.sizes.thumbnail ) {
                            previewUrl = attachment.sizes.thumbnail.url;
                        } else if ( attachment.sizes.medium ) {
                            previewUrl = attachment.sizes.medium.url;
                        }
                    }

                    $container.find( '#bw_showcase_image' ).val( attachment.id ? attachment.id : attachment.url );
                    $container.find( '#bw_showcase_image_preview' )
                        .html( '<img src="' + previewUrl + '" style="' + imageStyle + '" alt="" />' )
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
 * AJAX handler for searching WooCommerce products.
 */
function bw_search_products_ajax() {
    check_ajax_referer( 'bw_search_products', 'nonce' );

    $search_term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

    if ( empty( $search_term ) ) {
        wp_send_json( [] );
    }

    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        's'              => $search_term,
        'posts_per_page' => 20,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ];

    $query = new WP_Query( $args );

    $results = [];

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $results[] = [
                'id'   => get_the_ID(),
                'text' => get_the_title(),
            ];
        }
        wp_reset_postdata();
    }

    wp_send_json( $results );
}
add_action( 'wp_ajax_bw_search_products', 'bw_search_products_ajax' );

