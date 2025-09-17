<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Elementor_Widgets {
    public function __construct() {
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
        add_action( 'elementor/elements/categories_registered', [ $this, 'register_widget_category' ] );
    }

    public function register_widgets( $widgets_manager ) {
        $widget_file = trailingslashit( BW_PLUGIN_PATH ) . 'widgets/content/blackworkpro-products.php';

        if ( file_exists( $widget_file ) ) {
            require_once $widget_file;

            if ( class_exists( '\\BW\\Widgets\\BW_Blackworkpro_Products' ) ) {
                $widgets_manager->register( new \BW\Widgets\BW_Blackworkpro_Products() );
            }
        }
    }

    public function register_widget_category( $elements_manager ) {
        $elements_manager->add_category(
            'blackworkpro',
            [
                'title' => __( 'BlackworkPro', 'sas' ),
            ]
        );
    }
}

new BW_Elementor_Widgets();
