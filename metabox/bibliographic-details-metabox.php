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
 * Determine whether the current admin screen is the product editor.
 *
 * @param \WP_Screen|null $screen Optional screen object.
 * @return bool
 */
function bw_is_product_editor_screen( $screen = null ) {
    if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
        return false;
    }

    $screen = $screen instanceof WP_Screen ? $screen : get_current_screen();

    return $screen instanceof WP_Screen
        && 'product' === $screen->post_type
        && 'post' === $screen->base;
}

/**
 * Whether meta key hints should be visible for the current user.
 *
 * @return bool
 */
function bw_should_show_product_meta_keys() {
    return '1' === (string) get_user_meta( get_current_user_id(), 'bw_show_product_meta_keys', true );
}

/**
 * Add "Show meta keys" to Screen Options on product edit screens.
 *
 * @param string          $settings Existing screen settings HTML.
 * @param \WP_Screen|null $screen Current screen.
 * @return string
 */
function bw_add_product_meta_keys_screen_option( $settings, $screen ) {
    if ( ! bw_is_product_editor_screen( $screen ) ) {
        return $settings;
    }

    $checked = bw_should_show_product_meta_keys();

    $settings .= sprintf(
        '<fieldset class="metabox-prefs bw-screen-option-meta-keys"><legend>%1$s</legend><label for="bw-show-meta-keys-toggle"><input type="checkbox" id="bw-show-meta-keys-toggle" %2$s> %3$s</label></fieldset>',
        esc_html__( 'Blackwork', 'bw' ),
        checked( $checked, true, false ),
        esc_html__( 'Show meta keys', 'bw' )
    );

    return $settings;
}
add_filter( 'screen_settings', 'bw_add_product_meta_keys_screen_option', 10, 2 );

/**
 * Add a body class when meta key hints should be visible.
 *
 * @param string $classes Existing body classes.
 * @return string
 */
function bw_product_meta_keys_admin_body_class( $classes ) {
    if ( ! bw_is_product_editor_screen() ) {
        return $classes;
    }

    if ( bw_should_show_product_meta_keys() ) {
        $classes .= ' bw-show-meta-keys';
    }

    return $classes;
}
add_filter( 'admin_body_class', 'bw_product_meta_keys_admin_body_class' );

/**
 * Print Screen Options assets for product meta key visibility.
 */
function bw_print_product_meta_keys_screen_option_assets() {
    if ( ! bw_is_product_editor_screen() ) {
        return;
    }
    ?>
    <style>
        body.post-type-product:not(.bw-show-meta-keys) .bw-meta-key-hint {
            display: none !important;
        }
    </style>
    <script>
        jQuery(function($) {
            var $toggle = $('#bw-show-meta-keys-toggle');

            if (!$toggle.length) {
                return;
            }

            $toggle.on('change', function() {
                var enabled = $toggle.is(':checked');

                $('body').toggleClass('bw-show-meta-keys', enabled);

                $.post(ajaxurl, {
                    action: 'bw_toggle_product_meta_keys',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'bw_toggle_product_meta_keys' ) ); ?>',
                    enabled: enabled ? '1' : '0'
                });
            });
        });
    </script>
    <?php
}
add_action( 'admin_footer', 'bw_print_product_meta_keys_screen_option_assets' );

/**
 * Persist the "Show meta keys" user preference.
 */
function bw_toggle_product_meta_keys_ajax() {
    check_ajax_referer( 'bw_toggle_product_meta_keys', 'nonce' );

    if ( ! current_user_can( 'edit_products' ) ) {
        wp_send_json_error( [ 'message' => __( 'Permission denied.', 'bw' ) ], 403 );
    }

    $enabled = isset( $_POST['enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) );

    update_user_meta( get_current_user_id(), 'bw_show_product_meta_keys', $enabled ? '1' : '0' );

    wp_send_json_success();
}
add_action( 'wp_ajax_bw_toggle_product_meta_keys', 'bw_toggle_product_meta_keys_ajax' );

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
        '_bw_artist_name'       => __( 'Artist', 'bw' ),
        '_digital_source'       => __( 'Source', 'bw' ),
        '_digital_publisher'    => __( 'Publisher', 'bw' ),
        '_digital_year'         => __( 'Year', 'bw' ),
        '_digital_technique'    => __( 'Technique', 'bw' ),
    ];
}

/**
 * Admin-only field config for Digital Product Details.
 *
 * Keeps stored meta keys unchanged while improving editor clarity.
 *
 * @return array<string, array{label: string, id: string}>
 */
function bw_get_digital_product_admin_field_config() {
    return [
        '_digital_total_assets' => [
            'label' => __( 'Total Assets', 'bw' ),
            'id'    => 'digital_total_assets',
        ],
        '_digital_assets_list'  => [
            'label' => __( 'Assets List', 'bw' ),
            'id'    => 'digital_assets_list',
        ],
        '_digital_file_size'    => [
            'label' => __( 'File Size', 'bw' ),
            'id'    => 'digital_file_size',
        ],
        '_digital_formats'      => [
            'label' => __( 'Formats Included', 'bw' ),
            'id'    => 'digital_formats',
        ],
        '_bw_artist_name'       => [
            'label' => __( 'Digital Artist', 'bw' ),
            'id'    => 'digital_artist',
            'hint'  => '_digital_artist_name',
        ],
        '_digital_source'       => [
            'label' => __( 'Digital Source', 'bw' ),
            'id'    => 'digital_source',
        ],
        '_digital_publisher'    => [
            'label' => __( 'Digital Publisher', 'bw' ),
            'id'    => 'digital_publisher',
        ],
        '_digital_year'         => [
            'label' => __( 'Digital Year', 'bw' ),
            'id'    => 'digital_year',
        ],
        '_digital_technique'    => [
            'label' => __( 'Digital Technique', 'bw' ),
            'id'    => 'digital_technique',
        ],
    ];
}

/**
 * Admin-only field config for Books bibliographic details.
 *
 * @return array<string, array{label: string, id: string}>
 */
function bw_get_books_admin_field_config() {
    return [
        '_bw_biblio_title'     => [
            'label' => __( 'Book Title', 'bw' ),
            'id'    => 'book_title',
        ],
        '_bw_biblio_author'    => [
            'label' => __( 'Book Author', 'bw' ),
            'id'    => 'book_author',
        ],
        '_bw_biblio_publisher' => [
            'label' => __( 'Book Publisher', 'bw' ),
            'id'    => 'book_publisher',
        ],
        '_bw_biblio_year'      => [
            'label' => __( 'Book Year', 'bw' ),
            'id'    => 'book_year',
        ],
        '_bw_biblio_language'  => [
            'label' => __( 'Book Language', 'bw' ),
            'id'    => 'book_language',
        ],
        '_bw_biblio_binding'   => [
            'label' => __( 'Book Binding', 'bw' ),
            'id'    => 'book_binding',
        ],
        '_bw_biblio_pages'     => [
            'label' => __( 'Book Pages', 'bw' ),
            'id'    => 'book_pages',
        ],
        '_bw_biblio_edition'   => [
            'label' => __( 'Book Edition', 'bw' ),
            'id'    => 'book_edition',
        ],
        '_bw_biblio_condition' => [
            'label' => __( 'Book Condition', 'bw' ),
            'id'    => 'book_condition',
        ],
        '_bw_biblio_location'  => [
            'label' => __( 'Book Location', 'bw' ),
            'id'    => 'book_location',
        ],
    ];
}

/**
 * Admin-only field config for Prints bibliographic details.
 *
 * @return array<string, array{label: string, id: string}>
 */
function bw_get_prints_admin_field_config() {
    return [
        '_print_artist'     => [
            'label' => __( 'Print Artist', 'bw' ),
            'id'    => 'print_artist',
        ],
        '_print_publisher'  => [
            'label' => __( 'Print Publisher', 'bw' ),
            'id'    => 'print_publisher',
        ],
        '_print_year'       => [
            'label' => __( 'Print Year', 'bw' ),
            'id'    => 'print_year',
        ],
        '_print_technique'  => [
            'label' => __( 'Print Technique', 'bw' ),
            'id'    => 'print_technique',
        ],
        '_print_material'   => [
            'label' => __( 'Print Material', 'bw' ),
            'id'    => 'print_material',
        ],
        '_print_plate_size' => [
            'label' => __( 'Print Plate Size', 'bw' ),
            'id'    => 'print_plate_size',
        ],
        '_print_condition'  => [
            'label' => __( 'Print Condition', 'bw' ),
            'id'    => 'print_condition',
        ],
    ];
}

/**
 * List of compatibility rows.
 *
 * @return array<string, string> Key is the meta key, value is the frontend label.
 */
function bw_get_product_compatibility_fields() {
    return [
        '_bw_compatibility_adobe_illustrator_photoshop' => __( 'Adobe Illustrator, Photoshop', 'bw' ),
        '_bw_compatibility_figma_sketch_adobe_xd'       => __( 'Figma, Sketch, Adobe XD', 'bw' ),
        '_bw_compatibility_affinity_designer_photo'     => __( 'Affinity Designer & Photo', 'bw' ),
        '_bw_compatibility_coreldraw_inkscape'          => __( 'CorelDRAW, Inkscape', 'bw' ),
        '_bw_compatibility_canva_powerpoint'            => __( 'Canva, PowerPoint', 'bw' ),
        '_bw_compatibility_cricut_silhouette'           => __( 'Cricut, Silhouette', 'bw' ),
        '_bw_compatibility_blender_cinema4d'            => __( 'Blender, Cinema 4D', 'bw' ),
    ];
}

/**
 * Whether compatibility settings were explicitly saved for a product.
 *
 * @param int $post_id Product ID.
 * @return bool
 */
function bw_product_compatibility_is_configured( $post_id ) {
    return '1' === (string) get_post_meta( $post_id, '_bw_compatibility_configured', true );
}

/**
 * Determine if a compatibility option is enabled for a product.
 *
 * Default behavior for untouched products is "enabled".
 *
 * @param int    $post_id  Product ID.
 * @param string $meta_key Meta key.
 * @return bool
 */
function bw_is_product_compatibility_field_enabled( $post_id, $meta_key ) {
    if ( ! bw_product_compatibility_is_configured( $post_id ) ) {
        return true;
    }

    return '1' === (string) get_post_meta( $post_id, $meta_key, true );
}

/**
 * Get enabled compatibility rows for frontend rendering.
 *
 * @param int $post_id Product ID.
 * @return array<int, array<string, string>>
 */
function bw_get_enabled_product_compatibility_rows( $post_id ) {
    $rows = [];

    foreach ( bw_get_product_compatibility_fields() as $meta_key => $label ) {
        if ( ! bw_is_product_compatibility_field_enabled( $post_id, $meta_key ) ) {
            continue;
        }

        $rows[] = [
            'meta'  => $meta_key,
            'label' => $label,
        ];
    }

    return $rows;
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
    $digital_admin  = bw_get_digital_product_admin_field_config();
    $books_admin    = bw_get_books_admin_field_config();
    $prints_admin   = bw_get_prints_admin_field_config();
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
        .bw-meta-key-hint {
            display: block;
            margin-top: 2px;
            color: #888;
            font-family: monospace;
            font-size: 11px;
            font-weight: 400;
            text-transform: none;
            cursor: pointer;
            transition: color 0.15s ease;
            user-select: none;
            -webkit-user-select: none;
        }
        .bw-meta-key-hint:hover,
        .bw-meta-key-hint:focus {
            color: #555;
            outline: none;
        }
        .bw-meta-key-hint.is-copied {
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
    <div class="bw-biblio-section bw-biblio-section-digital">
        <h4 class="bw-biblio-section-title"><?php esc_html_e( 'Digital Product Details', 'bw' ); ?></h4>
        <div class="bw-biblio-section-subtitle"><?php esc_html_e( 'Collection content', 'bw' ); ?></div>
        <table class="bw-biblio-metabox-table">
            <tbody>
            <?php foreach ( $digital_fields as $meta_key => $label ) :
                $value = get_post_meta( $post->ID, $meta_key, true );
                $field_id    = isset( $digital_admin[ $meta_key ]['id'] ) ? $digital_admin[ $meta_key ]['id'] : $meta_key;
                $field_label = isset( $digital_admin[ $meta_key ]['label'] ) ? $digital_admin[ $meta_key ]['label'] : $label;
                $field_hint  = isset( $digital_admin[ $meta_key ]['hint'] ) ? $digital_admin[ $meta_key ]['hint'] : $meta_key;
                ?>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr( $field_id ); ?>">
                            <?php echo esc_html( $field_label ); ?>
                            <span class="bw-meta-key-hint" data-meta-key="<?php echo esc_attr( $field_hint ); ?>" tabindex="0"><?php echo esc_html( $field_hint ); ?></span>
                        </label>
                    </th>
                    <td>
                        <?php if ( '_digital_assets_list' === $meta_key ) : ?>
                            <textarea
                                id="<?php echo esc_attr( $field_id ); ?>"
                                name="<?php echo esc_attr( $meta_key ); ?>"
                                class="bw-biblio-textarea"
                            ><?php echo esc_textarea( $value ); ?></textarea>
                        <?php else : ?>
                            <input
                                type="text"
                                id="<?php echo esc_attr( $field_id ); ?>"
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

    <div class="bw-biblio-section bw-biblio-section-compatibility">
        <h4 class="bw-biblio-section-title"><?php esc_html_e( 'Compatibility', 'bw' ); ?></h4>
        <table class="bw-biblio-metabox-table">
            <tbody>
            <?php foreach ( bw_get_product_compatibility_fields() as $meta_key => $label ) : ?>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr( $meta_key ); ?>">
                            <?php echo esc_html( $label ); ?>
                            <span class="bw-meta-key-hint" data-meta-key="<?php echo esc_attr( $meta_key ); ?>" tabindex="0"><?php echo esc_html( $meta_key ); ?></span>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input
                                type="checkbox"
                                id="<?php echo esc_attr( $meta_key ); ?>"
                                name="<?php echo esc_attr( $meta_key ); ?>"
                                value="1"
                                <?php checked( bw_is_product_compatibility_field_enabled( $post->ID, $meta_key ) ); ?>
                            />
                            <?php esc_html_e( 'Show on frontend', 'bw' ); ?>
                        </label>
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
                $field_id    = isset( $books_admin[ $meta_key ]['id'] ) ? $books_admin[ $meta_key ]['id'] : $meta_key;
                $field_label = isset( $books_admin[ $meta_key ]['label'] ) ? $books_admin[ $meta_key ]['label'] : $label;
                ?>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr( $field_id ); ?>">
                            <?php echo esc_html( $field_label ); ?>
                            <span class="bw-meta-key-hint" data-meta-key="<?php echo esc_attr( $meta_key ); ?>" tabindex="0"><?php echo esc_html( $meta_key ); ?></span>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="<?php echo esc_attr( $field_id ); ?>"
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
                $field_id    = isset( $prints_admin[ $meta_key ]['id'] ) ? $prints_admin[ $meta_key ]['id'] : $meta_key;
                $field_label = isset( $prints_admin[ $meta_key ]['label'] ) ? $prints_admin[ $meta_key ]['label'] : $label;
                ?>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr( $field_id ); ?>">
                            <?php echo esc_html( $field_label ); ?>
                            <span class="bw-meta-key-hint" data-meta-key="<?php echo esc_attr( $meta_key ); ?>" tabindex="0"><?php echo esc_html( $meta_key ); ?></span>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="<?php echo esc_attr( $field_id ); ?>"
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
    $compatibility_fields = bw_get_product_compatibility_fields();

    foreach ( $fields as $meta_key => $label ) {
        $raw_value = isset( $_POST[ $meta_key ] ) ? wp_unslash( $_POST[ $meta_key ] ) : '';
        $value     = '_digital_assets_list' === $meta_key ? sanitize_textarea_field( $raw_value ) : sanitize_text_field( $raw_value );

        if ( '' !== $value ) {
            update_post_meta( $post_id, $meta_key, $value );
            if ( '_bw_artist_name' === $meta_key ) {
                update_post_meta( $post_id, '_digital_artist_name', $value );
            }
        } else {
            delete_post_meta( $post_id, $meta_key );
            if ( '_bw_artist_name' === $meta_key ) {
                delete_post_meta( $post_id, '_digital_artist_name' );
            }
        }
    }

    update_post_meta( $post_id, '_bw_compatibility_configured', '1' );

    foreach ( $compatibility_fields as $meta_key => $label ) {
        if ( isset( $_POST[ $meta_key ] ) ) {
            update_post_meta( $post_id, $meta_key, '1' );
        } else {
            delete_post_meta( $post_id, $meta_key );
        }
    }
}
add_action( 'save_post_product', 'bw_save_bibliographic_details_metabox' );
