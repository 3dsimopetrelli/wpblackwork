<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Widget_Loader {
    public function __construct() {
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
        add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_widgets' ] );
    }

    public function register_widgets( $widgets_manager = null ) {
        if ( null === $widgets_manager && class_exists( '\\Elementor\\Plugin' ) ) {
            $widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
        }

        if ( ! $widgets_manager ) {
            return;
        }

        $files = glob( __DIR__ . '/widgets/class-bw-*-widget.php' );
        foreach ( $files as $file ) {
            require_once $file;
            $class_name = $this->get_class_from_file( $file );
            if ( class_exists( $class_name ) ) {
                $widgets_manager->register( new $class_name() );
            }
        }
    }

    private function get_class_from_file( $file ) {
        $basename = basename( $file, '.php' );
        $basename = preg_replace( '/^class-/', '', $basename );
        $basename = preg_replace( '/-widget$/', '', $basename );

        $parts = array_filter( explode( '-', $basename ) );
        $parts = array_map( static function( $part ) {
            return ucfirst( $part );
        }, $parts );

        return 'Widget_' . implode( '_', $parts );
    }
}

new BW_Widget_Loader();
