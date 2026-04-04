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
        __( 'Showcase Slide Metabox', 'bw' ),
        'bw_render_digital_products_metabox',
        'product',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'bw_add_digital_products_metabox' );

/**
 * Enqueue metabox assets only on product editor screens.
 *
 * @param string $hook Admin hook suffix.
 */
function bw_enqueue_digital_products_metabox_assets( $hook ) {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
        return;
    }

    if ( ! function_exists( 'get_current_screen' ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( ! $screen || 'product' !== $screen->post_type ) {
        return;
    }

    // Media uploader (must be called before wp_head).
    wp_enqueue_media();

    // Select2 / SelectWoo.
    $select_handle = '';

    if ( wp_script_is( 'selectWoo', 'registered' ) ) {
        $select_handle = 'selectWoo';
    } elseif ( wp_script_is( 'select2', 'registered' ) ) {
        $select_handle = 'select2';
    } else {
        $fallback_js_rel  = 'assets/lib/select2/js/select2.full.min.js';
        $fallback_css_rel = 'assets/lib/select2/css/select2.css';
        $fallback_js_path = BW_MEW_PATH . $fallback_js_rel;
        $fallback_css_path = BW_MEW_PATH . $fallback_css_rel;

        if ( file_exists( $fallback_js_path ) ) {
            wp_register_script(
                'bw-select2-fallback',
                BW_MEW_URL . $fallback_js_rel,
                [ 'jquery' ],
                filemtime( $fallback_js_path ),
                true
            );
            $select_handle = 'bw-select2-fallback';
        }

        if ( file_exists( $fallback_css_path ) && ! wp_style_is( 'select2', 'registered' ) ) {
            wp_register_style(
                'bw-select2-fallback',
                BW_MEW_URL . $fallback_css_rel,
                [],
                filemtime( $fallback_css_path )
            );
        }
    }

    if ( '' !== $select_handle ) {
        wp_enqueue_script( $select_handle );
    }

    if ( wp_style_is( 'select2', 'registered' ) ) {
        wp_enqueue_style( 'select2' );
    } elseif ( wp_style_is( 'bw-select2-fallback', 'registered' ) ) {
        wp_enqueue_style( 'bw-select2-fallback' );
    }

    // Metabox admin styles.
    $css_path = BW_MEW_PATH . 'assets/css/bw-metabox-admin.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_style(
            'bw-metabox-admin-style',
            BW_MEW_URL . 'assets/css/bw-metabox-admin.css',
            [],
            filemtime( $css_path )
        );
    }

    // Metabox admin script.
    $js_path = BW_MEW_PATH . 'assets/js/bw-metabox-admin.js';
    if ( file_exists( $js_path ) ) {
        $js_deps = [ 'jquery' ];
        if ( '' !== $select_handle ) {
            $js_deps[] = $select_handle;
        }

        wp_enqueue_script(
            'bw-metabox-admin-script',
            BW_MEW_URL . 'assets/js/bw-metabox-admin.js',
            $js_deps,
            filemtime( $js_path ),
            true
        );

        wp_localize_script( 'bw-metabox-admin-script', 'bwMetaboxData', [
            'nonce' => wp_create_nonce( 'bw_search_products' ),
            'i18n'  => [
                'searchPlaceholder' => __( 'Search for a product...', 'bw' ),
                'mediaTitle'        => __( 'Select an image', 'bw' ),
                'mediaButton'       => __( 'Use this image', 'bw' ),
            ],
        ] );
    }
}
add_action( 'admin_enqueue_scripts', 'bw_enqueue_digital_products_metabox_assets' );

/**
 * Render a metabox label with meta key hint.
 *
 * @param string $for Input ID.
 * @param string $label Visible label.
 * @param string $meta_key Meta key hint.
 * @param bool   $strong Whether to wrap the label text in <strong>.
 */
function bw_render_metabox_label_with_hint( $for, $label, $meta_key, $strong = false ) {
    ?>
    <label for="<?php echo esc_attr( $for ); ?>">
        <?php if ( $strong ) : ?>
            <strong><?php echo esc_html( $label ); ?></strong>
        <?php else : ?>
            <?php echo esc_html( $label ); ?>
        <?php endif; ?>
        <span class="bw-meta-key-hint" data-meta-key="<?php echo esc_attr( $meta_key ); ?>" tabindex="0"><?php echo esc_html( $meta_key ); ?></span>
    </label>
    <?php
}

/**
 * Render the Digital Products metabox.
 *
 * @param \WP_Post $post Current post object.
 */
function bw_render_digital_products_metabox( $post ) {
    wp_nonce_field( 'bw_save_digital_products', 'bw_digital_products_nonce' );

    // Single DB call for all post meta.
    $all_meta = get_post_meta( $post->ID );
    $get_meta = static function ( $key ) use ( $all_meta ) {
        return isset( $all_meta[ $key ][0] ) ? $all_meta[ $key ][0] : '';
    };

    $showcase_image = $get_meta( '_bw_showcase_image' );
    if ( '' === $showcase_image ) {
        $showcase_image = $get_meta( '_product_showcase_image' );
    }

    $image_id  = 0;
    $image_url = '';
    if ( $showcase_image ) {
        if ( is_numeric( $showcase_image ) ) {
            $image_id = absint( $showcase_image );
        } else {
            $image_url      = esc_url_raw( $showcase_image );
            $maybe_image_id = attachment_url_to_postid( $showcase_image );
            if ( $maybe_image_id ) {
                $image_id = $maybe_image_id;
            }
        }
    }

    $showcase_title       = $get_meta( '_bw_showcase_title' );
    $showcase_description = $get_meta( '_bw_showcase_description' );

    $product_type = $get_meta( '_bw_product_type' );
    if ( ! in_array( $product_type, array( 'digital', 'physical' ), true ) ) {
        $product_type = 'digital';
    }

    $file_size    = $get_meta( '_bw_file_size' );
    if ( '' === $file_size ) {
        $file_size = $get_meta( '_product_size_mb' );
    }

    $assets_count = $get_meta( '_bw_assets_count' );
    if ( '' === $assets_count ) {
        $assets_count = $get_meta( '_product_assets_count' );
    }

    $formats = $get_meta( '_bw_formats' );
    if ( '' === $formats ) {
        $formats = $get_meta( '_product_formats' );
    }

    $info_1              = $get_meta( '_bw_info_1' );
    $info_2              = $get_meta( '_bw_info_2' );
    $product_button_text = $get_meta( '_product_button_text' );
    $product_button_link = $get_meta( '_product_button_link' );

    $texts_color = $get_meta( '_bw_texts_color' );
    if ( '' === $texts_color ) {
        $texts_color = $get_meta( '_product_color' );
    }

    $showcase_linked_product = $get_meta( '_bw_showcase_linked_product' );

    $preview_style = ( $image_id || $image_url ) ? 'display:block;' : 'display:none;';
    ?>
    <style>
        .bw-metabox-wrapper .bw-meta-key-hint {
            display: block;
            margin-top: 2px;
            color: #888;
            cursor: pointer;
            font-family: monospace;
            font-size: 11px;
            font-weight: 400;
            text-transform: none;
            transition: color 0.15s ease;
            user-select: none;
            -webkit-user-select: none;
        }
        .bw-metabox-wrapper .bw-meta-key-hint:hover,
        .bw-metabox-wrapper .bw-meta-key-hint:focus {
            color: #555;
            outline: none;
        }
        .bw-metabox-wrapper .bw-meta-key-hint.is-copied {
            color: #4caf50;
        }
    </style>
    <script>
        (function() {
            if (window.bwMetaKeyHintCopyBound) {
                return;
            }

            window.bwMetaKeyHintCopyBound = true;

            function fallbackCopy(text) {
                var textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', 'readonly');
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                textarea.style.pointerEvents = 'none';
                document.body.appendChild(textarea);
                textarea.select();

                try {
                    document.execCommand('copy');
                } finally {
                    document.body.removeChild(textarea);
                }
            }

            function copyMetaKey(text) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    return navigator.clipboard.writeText(text).catch(function() {
                        fallbackCopy(text);
                    });
                }

                fallbackCopy(text);
                return Promise.resolve();
            }

            function showCopiedState(element) {
                if (!element) {
                    return;
                }

                if (element.bwMetaKeyHintReset) {
                    clearTimeout(element.bwMetaKeyHintReset);
                }

                if (!element.dataset.originalText) {
                    element.dataset.originalText = element.textContent;
                }

                element.textContent = 'Copied!';
                element.classList.add('is-copied');

                element.bwMetaKeyHintReset = window.setTimeout(function() {
                    element.textContent = element.dataset.metaKey || element.dataset.originalText || '';
                    element.classList.remove('is-copied');
                    element.bwMetaKeyHintReset = null;
                }, 1000);
            }

            function handleMetaKeyHintInteraction(event) {
                var hint = event.target.closest('.bw-meta-key-hint');
                if (!hint) {
                    return;
                }

                if ('keydown' === event.type && 'Enter' !== event.key && ' ' !== event.key) {
                    return;
                }

                event.preventDefault();

                var metaKey = hint.dataset.metaKey || hint.textContent.trim();
                if (!metaKey) {
                    return;
                }

                copyMetaKey(metaKey).then(function() {
                    showCopiedState(hint);
                });
            }

            document.addEventListener('click', handleMetaKeyHintInteraction);
            document.addEventListener('keydown', handleMetaKeyHintInteraction);
        }());
    </script>
    <div class="bw-metabox-wrapper">
        <div class="bw-metabox-section">
            <h3><?php esc_html_e( 'General Settings', 'bw' ); ?></h3>
            <div class="bw-metabox-field-group">
                <?php bw_render_metabox_label_with_hint( 'bw_product_type', __( 'Product Type', 'bw' ), '_bw_product_type', true ); ?><br>
                <select name="bw_product_type" id="bw_product_type" style="min-width:200px;">
                    <option value="digital" <?php selected( $product_type, 'digital' ); ?>><?php esc_html_e( 'Digital product', 'bw' ); ?></option>
                    <option value="physical" <?php selected( $product_type, 'physical' ); ?>><?php esc_html_e( 'Physical product', 'bw' ); ?></option>
                </select>
                <span class="bw-field-description"><?php esc_html_e( 'Choose whether to show fields for digital or physical products: the metabox updates automatically.', 'bw' ); ?></span>
            </div>
            <div class="bw-metabox-field-group">
                <?php bw_render_metabox_label_with_hint( 'bw_texts_color', __( 'Texts Color', 'bw' ), '_bw_texts_color' ); ?><br>
                <input type="color" id="bw_texts_color" name="bw_texts_color" value="<?php echo esc_attr( $texts_color ? $texts_color : '#ffffff' ); ?>" style="width:100%;max-width:240px;" />
                <span class="bw-field-description"><?php esc_html_e( 'Color used for texts and badges in the BW Static Showcase widget.', 'bw' ); ?></span>
            </div>
        </div>

        <div class="bw-metabox-section">
            <h3><?php esc_html_e( 'Images', 'bw' ); ?></h3>
            <p class="bw-metabox-inline-info"><?php esc_html_e( 'Main image used in the BW Static Showcase.', 'bw' ); ?></p>
            <div class="bw-digital-products-showcase-field">
                <?php bw_render_metabox_label_with_hint( 'bw_showcase_image', __( 'Showcase Image', 'bw' ), '_bw_showcase_image', true ); ?><br>
                <div id="bw_showcase_image_preview" style="margin-top:6px;<?php echo esc_attr( $preview_style ); ?>">
                    <?php
                    if ( $image_id ) {
                        echo wp_get_attachment_image( $image_id, array( 120, 120 ), false, array(
                            'style' => 'border-radius:6px;width:120px;height:120px;object-fit:cover;',
                        ) );
                    } elseif ( $image_url ) {
                        echo '<img src="' . esc_url( $image_url ) . '" style="border-radius:6px;width:120px;height:120px;object-fit:cover;" alt="" />';
                    }
                    ?>
                </div>
                <input type="hidden" id="bw_showcase_image" name="bw_showcase_image" value="<?php echo esc_attr( $showcase_image ); ?>">
                <button type="button" class="button bw-upload-image"><?php esc_html_e( 'Choose Image', 'bw' ); ?></button>
                <button type="button" class="button bw-remove-image"><?php esc_html_e( 'Remove', 'bw' ); ?></button>
            </div>
        </div>

        <div class="bw-metabox-section">
            <h3><?php esc_html_e( 'Static Showcase Content', 'bw' ); ?></h3>
            <div class="bw-metabox-field-group">
                <?php bw_render_metabox_label_with_hint( 'bw_showcase_title', __( 'Showcase Title', 'bw' ), '_bw_showcase_title' ); ?>
                <input type="text" id="bw_showcase_title" name="bw_showcase_title" value="<?php echo esc_attr( $showcase_title ); ?>" style="width:100%;" />
            </div>
            <div class="bw-metabox-field-group">
                <?php bw_render_metabox_label_with_hint( 'bw_showcase_description', __( 'Showcase Description', 'bw' ), '_bw_showcase_description' ); ?>
                <textarea id="bw_showcase_description" name="bw_showcase_description" rows="4" style="width:100%;"><?php echo esc_textarea( $showcase_description ); ?></textarea>
            </div>
        </div>

        <div class="bw-metabox-section bw-digital-fields">
            <h3><?php esc_html_e( 'Digital Data', 'bw' ); ?></h3>
            <div class="bw-metabox-field-group">
                <?php bw_render_metabox_label_with_hint( 'bw_file_size', __( 'File Size (MB)', 'bw' ), '_bw_file_size' ); ?>
                <input type="text" id="bw_file_size" name="bw_file_size" value="<?php echo esc_attr( $file_size ); ?>" style="width:100%;" />
            </div>
            <div class="bw-metabox-field-group">
                <?php bw_render_metabox_label_with_hint( 'bw_assets_count', __( 'Assets Count', 'bw' ), '_bw_assets_count' ); ?>
                <input type="number" id="bw_assets_count" name="bw_assets_count" value="<?php echo esc_attr( $assets_count ); ?>" style="width:100%;" />
            </div>
            <div class="bw-metabox-field-group">
                <?php bw_render_metabox_label_with_hint( 'bw_formats', __( 'Formats (comma separated)', 'bw' ), '_bw_formats' ); ?>
                <input type="text" id="bw_formats" name="bw_formats" value="<?php echo esc_attr( $formats ); ?>" style="width:100%;" />
                <span class="bw-field-description"><?php esc_html_e( 'Enter formats separated by commas (e.g. SVG, PSD, PNG).', 'bw' ); ?></span>
            </div>
        </div>

        <div class="bw-metabox-section bw-physical-fields">
            <h3><?php esc_html_e( 'Physical Product Data', 'bw' ); ?></h3>
            <div class="bw-metabox-field-group">
                <?php bw_render_metabox_label_with_hint( 'bw_info_1', __( 'Info 1', 'bw' ), '_bw_info_1' ); ?>
                <input type="text" id="bw_info_1" name="bw_info_1" value="<?php echo esc_attr( $info_1 ); ?>" style="width:100%;" />
            </div>
            <div class="bw-metabox-field-group">
                <?php bw_render_metabox_label_with_hint( 'bw_info_2', __( 'Info 2', 'bw' ), '_bw_info_2' ); ?>
                <input type="text" id="bw_info_2" name="bw_info_2" value="<?php echo esc_attr( $info_2 ); ?>" style="width:100%;" />
            </div>
        </div>

        <div class="bw-metabox-section">
            <h3><?php esc_html_e( 'Call to Action', 'bw' ); ?></h3>
            <div class="bw-metabox-field-group">
                <?php bw_render_metabox_label_with_hint( 'bw_product_button_text', __( 'Button Text', 'bw' ), '_product_button_text' ); ?>
                <input type="text" id="bw_product_button_text" name="bw_product_button_text" value="<?php echo esc_attr( $product_button_text ); ?>" style="width:100%;" />
            </div>
            <div class="bw-metabox-field-group">
                <?php bw_render_metabox_label_with_hint( 'bw_product_button_link', __( 'Button Link (URL)', 'bw' ), '_product_button_link' ); ?>
                <input type="url" id="bw_product_button_link" name="bw_product_button_link" value="<?php echo esc_attr( $product_button_link ); ?>" style="width:100%;" />
            </div>
        </div>

        <div class="bw-metabox-section">
            <h3><?php esc_html_e( 'Linked Product for Showcase', 'bw' ); ?></h3>
            <div class="bw-metabox-field-group">
                <?php bw_render_metabox_label_with_hint( 'bw_showcase_linked_product', __( 'Linked Product', 'bw' ), '_bw_showcase_linked_product', true ); ?><br>
                <select name="bw_showcase_linked_product" id="bw_showcase_linked_product" class="bw-product-search-select" style="width:100%;">
                    <option value=""><?php esc_html_e( 'No product selected', 'bw' ); ?></option>
                    <?php
                    if ( $showcase_linked_product ) {
                        $linked_product = get_post( $showcase_linked_product );
                        if ( $linked_product && 'product' === $linked_product->post_type ) {
                            ?>
                            <option value="<?php echo esc_attr( $showcase_linked_product ); ?>" selected="selected">
                                <?php echo esc_html( $linked_product->post_title ); ?>
                            </option>
                            <?php
                        }
                    }
                    ?>
                </select>
                <span class="bw-field-description"><?php esc_html_e( 'Select the product shown by the widget when you enable the option "Use product from Showcase Slide Metabox".', 'bw' ); ?></span>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Save the Digital Products metabox data.
 *
 * @param int $post_id Post ID.
 */
function bw_save_digital_products( $post_id ) {
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

    $texts_color_raw = isset( $_POST['bw_texts_color'] ) ? wp_unslash( $_POST['bw_texts_color'] ) : '';
    $texts_color     = $texts_color_raw ? sanitize_hex_color( $texts_color_raw ) : '';

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

    if ( isset( $_POST['bw_showcase_linked_product'] ) ) {
        $showcase_linked_product = absint( wp_unslash( $_POST['bw_showcase_linked_product'] ) );
        update_post_meta( $post_id, '_bw_showcase_linked_product', $showcase_linked_product );
    }

    // Legacy compatibility.
    update_post_meta( $post_id, '_product_size_mb', $file_size );
    update_post_meta( $post_id, '_product_assets_count', $assets_count );
    update_post_meta( $post_id, '_product_formats', $formats );
    update_post_meta( $post_id, '_product_color', $texts_color );
}
add_action( 'save_post_product', 'bw_save_digital_products' );

/**
 * AJAX handler for searching WooCommerce products.
 * Accepts POST requests from Select2 in the metabox.
 */
function bw_search_products_ajax() {
    if ( ! current_user_can( 'edit_products' ) ) {
        wp_send_json_error( [ 'message' => __( 'Permission denied.', 'bw' ) ], 403 );
    }

    check_ajax_referer( 'bw_search_products', 'nonce' );

    $search_term = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';

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

    $query   = new WP_Query( $args );
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
