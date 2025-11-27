<?php
/**
 * Dynamic Tag per il nome dell'artista collegato ai prodotti WooCommerce.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( '\Elementor\Core\DynamicTags\Tag' ) && class_exists( '\Elementor\Modules\DynamicTags\Module' ) ) {

    /**
     * Dynamic Tag Elementor: Artist Name.
     */
    class BW_Artist_Name_Tag extends \Elementor\Core\DynamicTags\Tag {

        /**
         * Slug della dynamic tag.
         *
         * @return string
         */
        public function get_name() {
            return 'bw-artist-name';
        }

        /**
         * Etichetta mostrata in Elementor.
         *
         * @return string
         */
        public function get_title() {
            return __( 'Artist Name', 'bw' );
        }

        /**
         * Gruppo personalizzato in cui mostrare la tag.
         *
         * @return string
         */
        public function get_group() {
            return 'bw-dynamic-tags';
        }

        /**
         * Categoria (testo) per Elementor.
         *
         * @return array<int,string>
         */
        public function get_categories() {
            return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
        }

        /**
         * Stampa il valore nel frontend/editor.
         */
        public function render() {
            $value = $this->get_value();

            if ( ! empty( $value ) ) {
                echo esc_html( $value );
            }
        }

        /**
         * Recupera il nome artista dal metadato del prodotto corrente.
         *
         * @param array<string,mixed> $options Opzioni inutilizzate.
         *
         * @return string
         */
        public function get_value( array $options = [] ) {
            $post_id = $this->get_product_id_from_context();

            if ( ! $post_id ) {
                return '';
            }

            $artist_name = get_post_meta( $post_id, '_bw_artist_name', true );

            return is_string( $artist_name ) ? $artist_name : '';
        }

        /**
         * Determina l'ID prodotto corrente in frontend o editor Elementor.
         *
         * @return int
         */
        private function get_product_id_from_context() {
            $post_id = get_queried_object_id();

            if ( ! $post_id && isset( $GLOBALS['post']->ID ) ) {
                $post_id = (int) $GLOBALS['post']->ID;
            }

            if ( ! $post_id && isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof \WC_Product ) {
                $post_id = $GLOBALS['product']->get_id();
            }

            if ( class_exists( '\Elementor\Plugin' ) ) {
                $plugin = \Elementor\Plugin::instance();

                if ( isset( $plugin->editor ) && method_exists( $plugin->editor, 'is_edit_mode' ) && $plugin->editor->is_edit_mode() ) {
                    $editor_post_id = null;

                    if ( method_exists( $plugin->editor, 'get_post_id' ) ) {
                        $editor_post_id = $plugin->editor->get_post_id();
                    } elseif ( method_exists( $plugin->editor, 'get_current_post_id' ) ) {
                        $editor_post_id = $plugin->editor->get_current_post_id();
                    }

                    if ( $editor_post_id ) {
                        $post_id = (int) $editor_post_id;
                    }
                }

                $document = $plugin->documents->get_current();

                if ( $document && method_exists( $document, 'get_main_id' ) ) {
                    $document_id = (int) $document->get_main_id();

                    if ( $document_id ) {
                        $post_id = $document_id;
                    }
                }
            }

            return (int) $post_id;
        }
    }

    /**
     * Registra il gruppo e la dynamic tag personalizzata in Elementor.
     *
     * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags Gestore delle dynamic tags.
     */
    function bw_register_artist_name_dynamic_tag( $dynamic_tags ) {
        $dynamic_tags->register_group(
            'bw-dynamic-tags',
            [
                'title' => __( 'Blackwork Dynamic Tags', 'bw' ),
            ]
        );

        $dynamic_tags->register( new BW_Artist_Name_Tag() );
    }
    add_action( 'elementor/dynamic_tags/register', 'bw_register_artist_name_dynamic_tag' );
}
