<?php
/**
 * Campo URL completo per categorie prodotto WooCommerce
 *
 * Aggiunge un campo readonly con l'URL completo della categoria nella pagina Edit Category,
 * per facilitare il copy/paste dell'URL dall'admin di WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Aggiunge il campo URL alla pagina di modifica della categoria prodotto
 *
 * @param WP_Term $term L'oggetto term della categoria corrente
 */
function bw_add_category_url_field( $term ) {
    // Ottieni l'URL completo della categoria usando la funzione nativa di WordPress
    $term_link = get_term_link( $term, 'product_cat' );

    // Verifica che non ci siano errori
    if ( is_wp_error( $term_link ) ) {
        $term_link = '';
    }
    ?>
    <tr class="form-field bw-category-url-field">
        <th scope="row" valign="top">
            <label><?php esc_html_e( 'Category URL', 'bw' ); ?></label>
        </th>
        <td>
            <div style="display: flex; align-items: center; gap: 10px; max-width: 600px;">
                <input
                    type="text"
                    id="bw-category-url"
                    value="<?php echo esc_url( $term_link ); ?>"
                    readonly
                    style="flex: 1; padding: 8px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 13px; color: #2c3e50; cursor: text;"
                    onclick="this.select();"
                />
                <button
                    type="button"
                    id="bw-copy-url-btn"
                    class="button button-secondary"
                    style="white-space: nowrap; padding: 8px 16px;"
                >
                    <span class="dashicons dashicons-admin-page" style="vertical-align: middle; margin-right: 5px;"></span>
                    <?php esc_html_e( 'Copy URL', 'bw' ); ?>
                </button>
            </div>
            <p class="description" style="margin-top: 8px;">
                <?php esc_html_e( 'This is the complete URL for this product category. Click the field to select it or use the Copy button.', 'bw' ); ?>
            </p>
            <p id="bw-copy-feedback" style="color: #28a745; font-weight: 500; margin-top: 8px; display: none;">
                <span class="dashicons dashicons-yes-alt" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'URL copied to clipboard!', 'bw' ); ?>
            </p>
        </td>
    </tr>

    <style>
        /* Stile per il campo URL */
        .bw-category-url-field input:focus {
            outline: 2px solid #0073aa;
            outline-offset: 0;
            background-color: #fff;
        }

        .bw-category-url-field input::selection {
            background-color: #b3d9ff;
        }

        #bw-copy-url-btn:hover {
            background-color: #f0f0f1;
            border-color: #0a4b78;
        }

        #bw-copy-url-btn:active {
            transform: scale(0.98);
        }

        #bw-copy-feedback {
            animation: bw-fade-in 0.3s ease-in;
        }

        @keyframes bw-fade-in {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Funzione per copiare l'URL negli appunti
            $('#bw-copy-url-btn').on('click', function(e) {
                e.preventDefault();

                var urlField = $('#bw-category-url');
                var feedback = $('#bw-copy-feedback');
                var button = $(this);

                // Seleziona il testo
                urlField.select();

                try {
                    // Copia negli appunti usando l'API moderna se disponibile
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(urlField.val()).then(function() {
                            // Mostra feedback di successo
                            showCopyFeedback(button, feedback);
                        }).catch(function(err) {
                            // Fallback al metodo execCommand
                            copyFallback(urlField, button, feedback);
                        });
                    } else {
                        // Fallback per browser più vecchi
                        copyFallback(urlField, button, feedback);
                    }
                } catch (err) {
                    console.error('Errore durante la copia:', err);
                    copyFallback(urlField, button, feedback);
                }
            });

            // Metodo fallback per la copia
            function copyFallback(field, button, feedback) {
                try {
                    field[0].select();
                    var successful = document.execCommand('copy');
                    if (successful) {
                        showCopyFeedback(button, feedback);
                    } else {
                        console.error('Comando di copia fallito');
                    }
                } catch (err) {
                    console.error('Errore execCommand:', err);
                }
            }

            // Mostra il feedback visivo
            function showCopyFeedback(button, feedback) {
                // Cambia temporaneamente il testo del pulsante
                var originalText = button.html();
                button.html('<span class="dashicons dashicons-yes" style="vertical-align: middle; margin-right: 5px;"></span>' + '<?php echo esc_js( __( 'Copied!', 'bw' ) ); ?>');
                button.css('background-color', '#d4edda');
                button.css('border-color', '#28a745');
                button.css('color', '#155724');

                // Mostra il messaggio di feedback
                feedback.fadeIn(300);

                // Ripristina dopo 2 secondi
                setTimeout(function() {
                    button.html(originalText);
                    button.css('background-color', '');
                    button.css('border-color', '');
                    button.css('color', '');
                    feedback.fadeOut(300);
                }, 2000);
            }

            // Permetti la selezione facile quando si clicca sul campo
            $('#bw-category-url').on('click', function() {
                this.select();
            });
        });
    </script>
    <?php
}
add_action( 'product_cat_edit_form_fields', 'bw_add_category_url_field', 10, 1 );
